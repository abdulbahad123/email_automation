<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function __construct()
    {
        if(!get_option("auth_landing_page_status", 1)){
            if(auth()->id()){
                header("Location: ". route('app.dashboard'));
            }else{
                header("Location: ". route('login'));
            }
            exit;
        }
    }

    public function index(Request $request)
    {
        return view('guest::home');
    }

    public function pricing(Request $request)
    {
        return view('guest::pricing');
    }

    public function faqs(Request $request)
    {
        return view('guest::faqs');
    }
        
    public function contact(Request $request)
    {
        return view('guest::contact');
    }

    public function sendContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'message' => 'required|string',
        ]);

        // reCAPTCHA verification if enabled
        $recaptchaStatus = get_option('contact_recaptcha_status', get_option('auth_google_recaptcha_status', 1));
        $recaptchaSecret = get_option('contact_recaptcha_secret_key', get_option('auth_google_recaptcha_secret_key', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'));
        if (empty($recaptchaSecret)) {
            $recaptchaSecret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
        }

        if ($recaptchaStatus && !empty($recaptchaSecret)) {
            $token = $request->input('g-recaptcha-response');
            if (empty($token)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => __('Please complete the CAPTCHA verification.')], 422);
                }
                return back()->withInput()->with('error', __('Please complete the CAPTCHA verification.'));
            }

            try {
                $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($recaptchaSecret) . "&response=" . urlencode($token) . "&remoteip=" . urlencode($request->ip());
                $verifyResponse = @file_get_contents($verifyUrl);
                $responseData = json_decode($verifyResponse, true);
                if (!$responseData || empty($responseData['success'])) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['status' => 'error', 'message' => __('CAPTCHA verification failed. Please try again.')], 422);
                    }
                    return back()->withInput()->with('error', __('CAPTCHA verification failed. Please try again.'));
                }
            } catch (\Throwable $e) {}
        }

        // Ensure table exists
        \App\Models\ContactEnquiry::checkTable();

        $id_secure = function_exists('rand_string') ? rand_string() : md5(uniqid(rand(), true));

        \Illuminate\Support\Facades\DB::table('contact_enquiries')->insert([
            'id_secure' => $id_secure,
            'name' => strip_tags((string)$request->name),
            'email' => strip_tags((string)$request->email),
            'phone' => strip_tags((string)$request->phone),
            'subject' => strip_tags((string)$request->subject),
            'message' => strip_tags((string)$request->message),
            'ip_address' => $request->ip(),
            'status' => 0,
            'created' => time(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Thank you! Your enquiry has been received. Our team will contact you shortly.')
            ]);
        }

        return back()->with('success', __('Thank you! Your enquiry has been received. Our team will contact you shortly.'));
    }

    public function about(Request $request)
    {
        return view('guest::about');
    }

    public function blogs(Request $request, $cate_slug = null)
    {
        return view('guest::blogs');
    }

    public function blogDetail(Request $request, $id)
    {
        return view('guest::blog_detail');
    }

    public function pageNotFound(Request $request)
    {
        return view('guest::page_not_found');
    }

    public function privacyPolicy(Request $request)
    {
        return view('guest::privacy_policy');
    }

    public function termsOfService(Request $request)
    {
        return view('guest::terms_of_service');
    }
}
