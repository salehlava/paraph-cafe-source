<?php
/**
 * Starting menu data — used ONLY the first time, to fill the database.
 * After that, edit the menu in the admin panel (/admin → مدیریت منو).
 * Café menu data.
 * Each category: id, icon, title [fa, en], optional note [fa, en], items.
 * Each item: [fa name, en name, price]  — price is in thousand Toman; null = daily price.
 */

return [
    [
        'id'    => 'snacks',
        'icon'  => 'sandwich',
        'title' => ['fa' => 'ساندویچ و اسنک', 'en' => 'Sandwiches & Snacks'],
        'items' => [
            ['اسنک', 'Snack', 280],
            ['چیپس، قارچ و پنیر (کوچک)', 'Chips, Mushroom & Cheese (Small)', 320],
            ['چیپس، قارچ و پنیر (بزرگ)', 'Chips, Mushroom & Cheese (Large)', 500],
        ],
    ],
    [
        'id'    => 'herbal',
        'icon'  => 'leaf',
        'title' => ['fa' => 'چای و دمنوش', 'en' => 'Herbal Teas'],
        'items' => [
            ['چای و نبات', 'Tea with Rock Candy', 80],
            ['چای هل و دارچین', 'Tea (Cardamom + Cinnamon)', 130],
            ['گل گاوزبان', 'Borage Tea', 140],
            ['دمنوش آرامش', 'Tranquility (Relaxation Blend)', 150],
            ['دمنوش آویشن', 'Thyme Tea', 130],
            ['دمنوش شاه‌اسپرم', 'Shahsparan (Basil) Tea', 130],
            ['چای ترش', 'Sour Tea (Hibiscus)', 130],
        ],
    ],
    [
        'id'    => 'hot-espresso',
        'icon'  => 'cup',
        'title' => ['fa' => 'نوشیدنی گرم بر پایه اسپرسو', 'en' => 'Hot Espresso-Based Drinks'],
        'items' => [
            ['اسپرسو', 'Espresso', 140],
            ['عربیکا', 'Arabica', 180],
            ['آمریکانو', 'Americano', 150],
            ['کورتادو', 'Cortado', 170],
            ['لاته', 'Latte', 200],
            ['موکا', 'Mocha', 220],
            ['کاپوچینو', 'Cappuccino', 180],
            ['آفوگاتو', 'Affogato', 220],
            ['کارامل ماکیاتو', 'Caramel Macchiato', 230],
            ['سیروپ (افزودنی)', 'Syrup (Add-on)', 30, 'addon'],
        ],
    ],
    [
        'id'    => 'cold-espresso',
        'icon'  => 'ice',
        'title' => ['fa' => 'نوشیدنی سرد بر پایه اسپرسو', 'en' => 'Cold Espresso-Based Drinks'],
        'items' => [
            ['آیس آمریکانو', 'Iced Americano', 160],
            ['آیس لاته', 'Iced Latte', 220],
            ['آیس کارامل ماکیاتو', 'Iced Caramel Macchiato', 250],
            ['آیس موکا', 'Iced Mocha', 250],
            ['آیس چاکلت', 'Iced Chocolate', 220],
            ['آیس وایت چاکلت', 'Iced White Chocolate', 220],
            ['آیس پینک چاکلت', 'Iced Pink Chocolate', 220],
            ['آیس ماچا لاته', 'Iced Matcha Latte', 250],
            ['اورنج آمریکانو', 'Orange Americano', 220],
            ['سیروپ (افزودنی)', 'Syrup (Add-on)', 30, 'addon'],
        ],
    ],
    [
        'id'    => 'hot-intl',
        'icon'  => 'mug',
        'title' => ['fa' => 'نوشیدنی‌های گرم بین‌المللی', 'en' => 'Hot International Drinks'],
        'items' => [
            ['هات چاکلت', 'Hot Chocolate', 200],
            ['وایت چاکلت', 'White Chocolate', 200],
            ['پینک چاکلت', 'Pink Chocolate', 200],
            ['دارک چاکلت', 'Dark Chocolate', 220],
            ['ماسالا چای', 'Masala Chai', 200],
            ['چای لاته', 'Chai Latte', 190],
            ['چای کرک', 'Karak Chai', 190],
            ['ماچا لاته', 'Matcha Latte', 230],
        ],
    ],
    [
        'id'    => 'brewed',
        'icon'  => 'drip',
        'title' => ['fa' => 'قهوه دمی', 'en' => 'Brewed Coffees'],
        'items' => [
            ['قهوه ترک / قهوه سفید', 'Turkish / White Coffee', 140],
            ['وی‌شصت (V60)', 'V60', 250],
            ['کلد برو', 'Cold Brew', 250],
        ],
    ],
    [
        'id'    => 'smoothies',
        'icon'  => 'smoothie',
        'title' => ['fa' => 'اسموتی', 'en' => 'Smoothies'],
        'items' => [
            ['مانگو دریم', 'Mango Dream', 280],
            ['سیتروس اسپلش', 'Citrus Splash', 250],
            ['سان‌ست وایب', 'Sunset Vibe', 300],
            ['چری راش', 'Cherry Rush', 250],
        ],
    ],
    [
        'id'    => 'cakes',
        'icon'  => 'cake',
        'title' => ['fa' => 'کیک و دسر', 'en' => 'Cakes'],
        'note'  => ['fa' => 'قیمت روز', 'en' => 'Daily price'],
        'items' => [
            ['کیک روز', 'Cake of the Day', null],
            ['تیرامیسو', 'Tiramisu', null],
            ['باقلوا', 'Baklava', null],
            ['چیزکیک لوتوس', 'Lotus Cheesecake', null],
            ['کیک سه شیر', 'Tres Leches Cake', null],
        ],
    ],
    [
        'id'    => 'mocktails',
        'icon'  => 'glass',
        'title' => ['fa' => 'ماکتیل', 'en' => 'Mocktails'],
        'items' => [
            ['موهیتو', 'Mojito', 220],
            ['رد موهیتو', 'Red Mojito', 240],
            ['بلو موهیتو', 'Blue Mojito', 240],
            ['لیموناد', 'Lemonade', 200],
            ['بابل‌گام', 'Bubblegum', 230],
            ['بلاد', 'Blood', 250],
            ['گروت', 'Groot', 210],
            ['وایلد وست', 'Wild West', 250],
            ['پاستیل', 'Pastille (Gummy)', 250],
            ['هاوایی', 'Hawaii', 270],
            ['کهکشان آبی', 'Blue Galaxy', 270],
        ],
    ],
    [
        'id'    => 'shakes',
        'icon'  => 'shake',
        'title' => ['fa' => 'شیک', 'en' => 'Shakes'],
        'items' => [
            ['اسپرسو شیک', 'Espresso Shake', 320],
            ['وانیل', 'Vanilla', 220],
            ['توت‌فرنگی', 'Strawberry', 240],
            ['شکلات', 'Chocolate', 250],
            ['نوتلا', 'Nutella', 260],
            ['بادام‌زمینی', 'Peanut', 300],
            ['پروتئین رژیمی', 'Diet Protein', 350],
            ['موز شکلات', 'Banana Chocolate', 270],
        ],
    ],
];
