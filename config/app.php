<?php
/**
 * Ilova (application) konfiguratsiyasi.
 */

return [
    // Ilova nomi (topbar va sarlavhalarda ishlatiladi).
    'name' => 'ADPI Doktorantura monitoringi',
    'short_name' => 'ADPI Monitoring',
    'institute' => 'Andijon davlat pedagogika instituti',

    // Muhit: local | production
    'env' => getenv('APP_ENV') ?: 'local',

    // Xatolarni ko'rsatish (faqat local muhitda).
    'debug' => (getenv('APP_ENV') ?: 'local') !== 'production',

    // Til (interfeys tili).
    'locale' => 'uz',

    // Bazaviy URL (bo'sh bo'lsa nisbiy yo'llar ishlatiladi).
    'base_url' => getenv('APP_URL') ?: '',

    // Vaqt mintaqasi.
    'timezone' => 'Asia/Tashkent',
];
