<?php

namespace Modules\AdminLandingPage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLandingPageController extends Controller
{
    public static function cleanLandingText($val)
    {
        if (empty($val) || !is_string($val)) {
            return $val;
        }

        $val = preg_replace('/automation(kkr|kr|sk|sr|ff|f|k)+/i', 'automation', $val);
        $val = preg_replace('/reports(kkr|kr|sk|sr|ff|f|k)*(\.|\b)/i', 'reports$2', $val);
        $val = preg_replace('/reporting(kkr|kr|sk|sr|ff|f|k)*(\.|\b)/i', 'reporting$2', $val);
        $val = preg_replace('/workspace(kkr|kr|sk|sr|ff|f|k)+/i', 'workspace', $val);
        $val = preg_replace('/modules(r|kr|sk|sr|ff|f|k)+/i', 'modules', $val);
        $val = preg_replace('/coverage(kkr|kr|sk|sr|ff|f|k)+/i', 'coverage', $val);
        $val = preg_replace('/workflow(kkr|kr|sk|sr|ff|f|k)+/i', 'workflow', $val);
        $val = preg_replace('/TOOL(kkr|kr|sk|sr|ff|f|k)+/i', 'TOOL', $val);
        $val = preg_replace('/(\w+)\.(k|kr|sk|kkr|sr|f|ff)\s*$/i', '$1.', $val);
        $val = preg_replace('/([a-zA-Z]{3,})(kkr|sk|kr|sr|ff)(\s*<|\s*$)/i', '$1$3', $val);
        $val = preg_replace('/\b(defaull|default)\s+launching\b/i', '', $val);

        return trim($val);
    }

    public function settings()
    {
        // Auto-clean any legacy test suffixes stored in database options
        try {
            $keysToCheck = [
                'landing_hero_title',
                'landing_hero_subtitle_bold',
                'landing_hero_subtitle_desc',
                'landing_hero_btn1_text',
                'landing_hero_btn2_text',
                'landing_hero_badge',
                'landing_stat1_label',
                'landing_stat2_label',
                'landing_stat3_label',
                'landing_stack_title',
                'landing_stack_subtitle',
            ];
            $rows = DB::table('options')->whereIn('name', $keysToCheck)->get();
            foreach ($rows as $row) {
                $cleaned = self::cleanLandingText($row->value);
                if ($cleaned !== $row->value) {
                    DB::table('options')->where('id', $row->id)->update(['value' => $cleaned]);
                }
            }
        } catch (\Throwable $e) {}

        return view('adminlandingpage::index');
    }

    public function save(Request $request)
    {
        $posts = $request->except(['_token']);

        // Handle OTP notice logo file upload
        if ($request->hasFile('otp_notice_image_file')) {
            $otpFile = $request->file('otp_notice_image_file');
            if ($otpFile && $otpFile->isValid()) {
                $uploadDir = public_path('uploads/otp_notice');
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'otp_logo_' . time() . '.' . $otpFile->getClientOriginalExtension();
                $otpFile->move($uploadDir, $filename);
                $posts['otp_notice_image'] = 'uploads/otp_notice/' . $filename;
            }
        }

        // Handle dynamic testimonials up to 40 items with ratings and avatar images
        if ($request->has('testimonials_data')) {
            $testiData = $request->input('testimonials_data');
            if (is_string($testiData)) {
                $testiData = json_decode($testiData, true);
            }
            if (is_array($testiData)) {
                // Strict limit of 40 testimonials
                $testiData = array_slice($testiData, 0, 40);

                // Handle file uploads for testimonial avatars
                if ($request->hasFile('testi_avatar_files')) {
                    $uploadDir = public_path('uploads/testimonials');
                    if (!file_exists($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    foreach ($request->file('testi_avatar_files') as $idx => $imgFile) {
                        if ($imgFile && $imgFile->isValid() && isset($testiData[$idx])) {
                            $filename = 'testi_' . time() . '_' . $idx . '.' . $imgFile->getClientOriginalExtension();
                            $imgFile->move($uploadDir, $filename);
                            $testiData[$idx]['img'] = 'uploads/testimonials/' . $filename;
                        }
                    }
                }

                $posts['landing_testimonials_data'] = json_encode($testiData, JSON_UNESCAPED_UNICODE);

                // Cross-populate individual indexed keys for backward compatibility
                foreach ($testiData as $i => $item) {
                    $num = $i + 1;
                    $posts["landing_testi{$num}_name"] = $item['name'] ?? '';
                    $posts["landing_testi{$num}_role"] = $item['role'] ?? '';
                    $posts["landing_testi{$num}_text"] = $item['text'] ?? '';
                    $posts["landing_testi{$num}_rating"] = $item['rating'] ?? 5;
                    $posts["landing_testi{$num}_img"] = $item['img'] ?? '';
                }
            }
        }

        $isStandalone = filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN);
        $sisterDbs = $isStandalone ? [] : [
            'nooryak_whatsapp',
            'nooryak_whatsappbulk',
            'nooryak_whatautomation',
            'nooryak_replix_automation',
            'replix_automation',
        ];
        $currentDb = config('database.connections.mysql.database');

        foreach ($posts as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_string($value) && !in_array($name, ['landing_testimonials_data', 'landing_pricing_faq_data'])) {
                $value = self::cleanLandingText($value);
            }
            if ($value === null) {
                $value = "";
            }
            DB::table('options')->updateOrInsert(
                ['name' => $name],
                ['value' => (string)$value]
            );

            // Cross-sync to sister WhatsApp DBs if accessible
            foreach ($sisterDbs as $sDb) {
                if ($sDb === $currentDb) continue;
                try {
                    DB::statement("INSERT INTO `{$sDb}`.options (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$name, (string)$value]);
                } catch (\Throwable $e) {}
            }
        }

        try {
            app()->forgetInstance('options');
        } catch (\Throwable $e) {}

        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Throwable $e) {}

        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        if (function_exists('ms')) {
            ms([
                "status" => 1,
                "message" => __("Landing page settings saved successfully!")
            ]);
        }

        return response()->json([
            "status" => 1,
            "message" => __("Landing page settings saved successfully!")
        ]);
    }
}