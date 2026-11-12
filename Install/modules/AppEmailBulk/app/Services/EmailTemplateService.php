<?php

namespace Modules\AppEmailBulk\Services;

class EmailTemplateService
{
    /**
     * Default marketing & outreach email templates selectable in the composer tabs.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTemplates(): array
    {
        return [
            [
                'id' => 'marketing_services',
                'name' => __('Marketing & Services Pitch'),
                'category' => __('Marketing'),
                'icon' => 'fa-light fa-bullhorn',
                'accent' => '#4f46e5',
                'description' => __('Best for agencies and B2B service promotions (Web, App, Digital Marketing).'),
                'default_subject' => __('Scale your business with custom Web & App solutions'),
                'default_brand' => config('app.name', 'Nooryak Technologies'),
                'default_badge' => __('OUR SERVICES'),
                'default_headline' => __('Build Fast, Scalable Websites & Mobile Apps'),
                'default_body' => "Hi [name],\n\nWe help growing businesses launch high-converting websites, mobile apps, and custom automation systems tailored to your goals.\n\n- Custom Web & E-Commerce Development\n- Android & iOS Mobile Applications\n- CRM, WhatsApp & Email Automation\n\nReply to this email or book a quick discovery call below.",
                'default_cta_text' => __('Get a Free Consultation'),
                'default_cta_url' => (string) config('app.url', 'https://nooryak.com'),
                'default_footer' => __('Sent with care by Nooryak Technologies'),
            ],
            [
                'id' => 'promo_offer',
                'name' => __('Special Offer & Discount'),
                'category' => __('Promotion'),
                'icon' => 'fa-light fa-tags',
                'accent' => '#059669',
                'description' => __('Highlight a limited-time discount, launch deal, or seasonal promotion.'),
                'default_subject' => __('Limited-Time Offer: Save 25% on your next project'),
                'default_brand' => config('app.name', 'Nooryak Technologies'),
                'default_badge' => __('LIMITED OFFER'),
                'default_headline' => __('Unlock 25% Off This Week Only'),
                'default_body' => "Hi [name],\n\nFor a limited time, we are offering 25% off on new web development, mobile app, and automation packages.\n\nClaim your spot before the offer ends and start launching faster.",
                'default_cta_text' => __('Claim Offer Now'),
                'default_cta_url' => (string) config('app.url', 'https://nooryak.com'),
                'default_footer' => __('Offer valid for a limited time only.'),
            ],
            [
                'id' => 'newsletter',
                'name' => __('Newsletter & Product Update'),
                'category' => __('Newsletter'),
                'icon' => 'fa-light fa-newspaper',
                'accent' => '#0284c7',
                'description' => __('Share product updates, new features, or monthly company news cleanly.'),
                'default_subject' => __('What is new this month — Features & Updates'),
                'default_brand' => config('app.name', 'Nooryak Technologies'),
                'default_badge' => __('MONTHLY UPDATE'),
                'default_headline' => __('New Features to Grow Your Reach'),
                'default_body' => "Hi [name],\n\nHere is a quick look at what we launched this month to help your team save time and convert more leads.\n\nExplore the latest improvements and let us know what you think.",
                'default_cta_text' => __('Explore Updates'),
                'default_cta_url' => (string) config('app.url', 'https://nooryak.com'),
                'default_footer' => __('Thank you for staying connected with us.'),
            ],
            [
                'id' => 'follow_up',
                'name' => __('Clean B2B Outreach'),
                'category' => __('Outreach'),
                'icon' => 'fa-light fa-handshake',
                'accent' => '#334155',
                'description' => __('Minimalist high-deliverability layout for client outreach and follow-ups.'),
                'default_subject' => __('Quick question regarding [company]'),
                'default_brand' => config('app.name', 'Nooryak Technologies'),
                'default_badge' => __('QUICK NOTE'),
                'default_headline' => __('Quick Follow-Up for You'),
                'default_body' => "Hi [name],\n\nI wanted to follow up briefly and see if you are looking to upgrade your website, mobile app, or customer automation this quarter.\n\nHappy to share examples and pricing whenever you have 10 minutes.",
                'default_cta_text' => __('Schedule 10-Min Call'),
                'default_cta_url' => (string) config('app.url', 'https://nooryak.com'),
                'default_footer' => __('Best regards, Your Growth Team'),
            ],
            [
                'id' => 'event_invite',
                'name' => __('Demo & Webinar Invite'),
                'category' => __('Invitation'),
                'icon' => 'fa-light fa-calendar-star',
                'accent' => '#9333ea',
                'description' => __('Invite prospects to a live product demo, webinar, or consultation session.'),
                'default_subject' => __('You are invited: Live Walkthrough & Q&A'),
                'default_brand' => config('app.name', 'Nooryak Technologies'),
                'default_badge' => __('INVITATION'),
                'default_headline' => __('Join Our Live Strategy & Demo Session'),
                'default_body' => "Hi [name],\n\nWe are hosting a short live walkthrough showing how teams automate outreach and scale web & app delivery.\n\nReserve your seat below — spots are limited.",
                'default_cta_text' => __('Reserve Your Spot'),
                'default_cta_url' => (string) config('app.url', 'https://nooryak.com'),
                'default_footer' => __('We look forward to seeing you there.'),
            ],
        ];
    }

    /**
     * Inject an inline/banner image into HTML body when user uploads one outside TinyMCE.
     */
    public static function injectInlineImage(string $htmlBody, string $imageSrc): string
    {
        $imageSrc = trim($imageSrc);
        if ($imageSrc === '') {
            return $htmlBody;
        }

        // Avoid duplicate injection if already referenced in the body
        if (str_contains($htmlBody, $imageSrc) || str_contains($htmlBody, basename($imageSrc))) {
            return $htmlBody;
        }

        $imgTag = '<div style="margin:0 0 18px 0;text-align:center;">'
            . '<img src="' . e($imageSrc) . '" alt="Email Image" style="max-width:100%;height:auto;border-radius:8px;display:inline-block;">'
            . '</div>';

        // Place right before a marker if present, else prepend cleanly
        if (str_contains($htmlBody, '<!--INLINE_IMAGE_SLOT-->')) {
            return str_replace('<!--INLINE_IMAGE_SLOT-->', $imgTag, $htmlBody);
        }

        return $imgTag . $htmlBody;
    }

    /**
     * Normalize plain text into email-safe HTML paragraphs if needed.
     */
    public static function normalizeBodyHtml(string $body): string
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return '';
        }

        if (strip_tags($trimmed) === $trimmed) {
            return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1e293b;">'
                . nl2br(e($trimmed))
                . '</div>';
        }

        return $trimmed;
    }
}
