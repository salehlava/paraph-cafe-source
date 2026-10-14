<?php
/**
 * Defaults and UI translations.
 * Site info, club and SMS settings are editable in the admin panel (/admin);
 * the values below are only used until they are changed there.
 */

const SETTINGS_DEFAULTS = [
    // Café info
    'name_fa'    => 'کافه پاراف',
    'name_en'    => 'Paraph Café',
    'tagline_fa' => 'رد پای نگاهت، ذهن و روحت را اینجا پاراف کن',
    'tagline_en' => 'Leave the trace of your gaze — sign your mind and soul here',
    'address_fa' => 'انتهای خیابان ملک، ابتدای کوچه مسعودی، جنب فروشگاه دیوید جونز',
    'address_en' => 'End of Malek St., start of Masoudi Alley, next to David Jones store',
    'phone'      => '+98 900 000 0000',
    'hours_fa'   => 'هر روز ۸ صبح تا ۱۱ شب',
    'hours_en'   => 'Daily 8 AM – 11 PM',
    'instagram'  => 'https://instagram.com/paraph.cafe',

    // Colours (Admin → ظاهر و رنگ‌ها)
    'color_primary'  => '#4a2c1d',   // chocolate: buttons, icons, active tabs
    'color_dark'     => '#2b1a12',   // dark chocolate: header, hero, start screen
    'color_accent'   => '#c08a5b',   // caramel: highlights
    'color_cream'    => '#f3e5cf',   // cream: price labels, light text on dark
    'color_bg_light' => '#f5ede1',   // page background (light mode)
    'color_bg_dark'  => '#0f0a07',   // page background (dark mode)

    // Customer club
    'club_enabled' => '1',
    'level_silver' => '500',
    'level_gold'   => '1500',

    // SMS panel
    'sms_driver'      => 'log',
    'sms_api_key'     => '',
    'sms_sender'      => '',
    'sms_username'    => '',
    'sms_password'    => '',
    'sms_otp_tpl'     => "کد تایید باشگاه مشتریان کافه پاراف: {code}",
    'sms_welcome_tpl' => "{name} عزیز، به باشگاه مشتریان کافه پاراف خوش آمدید ☕",
];

const LANGS = ['fa', 'en'];
const DEFAULT_LANG = 'fa';

const STRINGS = [
    'menu'        => ['fa' => 'منو', 'en' => 'Menu'],
    'search'      => ['fa' => 'جستجو در منو…', 'en' => 'Search the menu…'],
    'unit'        => ['fa' => 'هزار ت', 'en' => 'K'],
    'daily_price' => ['fa' => 'قیمت روز', 'en' => 'Daily price'],
    'unavailable' => ['fa' => 'ناموجود', 'en' => 'Sold out'],
    'no_results'  => ['fa' => 'موردی پیدا نشد', 'en' => 'Nothing found'],
    'theme'       => ['fa' => 'تغییر تم', 'en' => 'Toggle theme'],
    'top'         => ['fa' => 'بازگشت به بالا', 'en' => 'Back to top'],
    'hours'       => ['fa' => 'ساعات کاری', 'en' => 'Opening hours'],
    'contact'     => ['fa' => 'تماس', 'en' => 'Contact'],
    'address'     => ['fa' => 'آدرس', 'en' => 'Address'],
    'rights'      => ['fa' => 'تمامی حقوق محفوظ است', 'en' => 'All rights reserved'],

    // Customer club
    'club'          => ['fa' => 'باشگاه مشتریان', 'en' => 'Customer Club'],
    'club_intro'    => ['fa' => 'با شماره موبایل وارد شوید، امتیاز جمع کنید و از تخفیف‌ها و هدایای ویژه باخبر شوید.', 'en' => 'Sign in with your mobile number, collect points and get special offers and gifts.'],
    'mobile'        => ['fa' => 'شماره موبایل', 'en' => 'Mobile number'],
    'send_code'     => ['fa' => 'دریافت کد تایید', 'en' => 'Send code'],
    'code'          => ['fa' => 'کد تایید', 'en' => 'Verification code'],
    'code_sent'     => ['fa' => 'کد تایید به شماره %s پیامک شد.', 'en' => 'A code was sent to %s.'],
    'verify'        => ['fa' => 'تایید', 'en' => 'Verify'],
    'change_number' => ['fa' => 'تغییر شماره', 'en' => 'Change number'],
    'resend'        => ['fa' => 'ارسال دوباره', 'en' => 'Resend'],
    'your_name'     => ['fa' => 'نام و نام خانوادگی', 'en' => 'Full name'],
    'birthday'      => ['fa' => 'تاریخ تولد (اختیاری)', 'en' => 'Birthday (optional)'],
    'birthday_ph'   => ['fa' => 'مثلا ۱۳۷۵/۰۵/۱۲', 'en' => 'e.g. 1996-08-02'],
    'join'          => ['fa' => 'عضویت در باشگاه', 'en' => 'Join the club'],
    'welcome_new'   => ['fa' => 'یک قدم مانده! اطلاعاتتان را وارد کنید.', 'en' => 'One step left! Tell us about you.'],
    'points'        => ['fa' => 'امتیاز', 'en' => 'Points'],
    'level'         => ['fa' => 'سطح', 'en' => 'Level'],
    'member_since'  => ['fa' => 'عضو از', 'en' => 'Member since'],
    'sign_out'      => ['fa' => 'خروج', 'en' => 'Sign out'],
    'back_to_menu'  => ['fa' => 'بازگشت به منو', 'en' => 'Back to menu'],
    'err_phone'     => ['fa' => 'شماره موبایل معتبر نیست.', 'en' => 'Invalid mobile number.'],
    'err_wait'      => ['fa' => 'لطفا %s ثانیه دیگر دوباره تلاش کنید.', 'en' => 'Please try again in %s seconds.'],
    'err_code'      => ['fa' => 'کد وارد شده اشتباه یا منقضی است.', 'en' => 'The code is wrong or expired.'],
    'err_sms'       => ['fa' => 'ارسال پیامک ناموفق بود. لطفا بعدا تلاش کنید.', 'en' => 'Could not send SMS. Please try later.'],
    'err_name'      => ['fa' => 'لطفا نام خود را وارد کنید.', 'en' => 'Please enter your name.'],
    'err_limit'     => ['fa' => 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کنید.', 'en' => 'Too many requests. Try again later.'],
    'test_mode'     => ['fa' => 'حالت تست پیامک فعال است — کد شما:', 'en' => 'SMS test mode is on — your code:'],
    'club_closed'   => ['fa' => 'باشگاه مشتریان در حال حاضر غیرفعال است.', 'en' => 'The customer club is currently closed.'],
];

const COLOR_KEYS = ['color_primary', 'color_dark', 'color_accent', 'color_cream', 'color_bg_light', 'color_bg_dark'];

/** Ready-made colour themes for Admin → ظاهر و رنگ‌ها: [name, primary, dark, accent, cream, bg light, bg dark]. */
const COLOR_PRESETS = [
    'chocolate' => ['شکلاتی (پیش‌فرض)', '#4a2c1d', '#2b1a12', '#c08a5b', '#f3e5cf', '#f5ede1', '#0f0a07'],
    'espresso'  => ['اسپرسو',            '#3e2723', '#1b120f', '#a1887f', '#efebe9', '#f7f3f0', '#0d0a09'],
    'pistachio' => ['پسته‌ای',            '#4f6b3a', '#1f2b17', '#b5c97a', '#eef3e0', '#f4f6ec', '#0b0f08'],
    'rose'      => ['رز',                 '#8a3b52', '#2e1119', '#e0a3b0', '#f8e6ea', '#fbf1f3', '#12080b'],
    'navy'      => ['سرمه‌ای و طلایی',    '#23395b', '#0f1a2b', '#c9a227', '#f1ead6', '#f4f2ec', '#070b12'],
    'noir'      => ['مشکی و طلایی',       '#2b2b2b', '#111111', '#d4af37', '#f5ecd0', '#f6f4ef', '#050505'],
];

const LEVELS = [
    'bronze' => ['fa' => 'برنزی', 'en' => 'Bronze'],
    'silver' => ['fa' => 'نقره‌ای', 'en' => 'Silver'],
    'gold'   => ['fa' => 'طلایی', 'en' => 'Gold'],
];
