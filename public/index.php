<?php
// public/index.php
// Mehrsprachiger Einleitungstext + Infoblock (keine externen Ressourcen).
mb_internal_encoding('UTF-8');

// verfügbare Sprachen
$languages = [
  'de' => 'Deutsch',
  'en' => 'English',
  'fr' => 'Français',
  'uk' => 'Українська',
  'ar' => 'العربية',
  'ru' => 'Русский',
  'tr' => 'Türkçe',
  'fa' => 'فارسی',
];

// einfache Erkennung der gewünschten Sprache (Query > Cookie > Browser > de)
$lang = strtolower($_GET['lang'] ?? ($_COOKIE['lang'] ?? ''));
if (!array_key_exists($lang, $languages)) {
    $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    foreach ($languages as $code => $label) {
        if (strpos($accept, $code) !== false) { $lang = $code; break; }
    }
    if (!array_key_exists($lang, $languages)) $lang = 'de';
}
setcookie('lang', $lang, time()+60*60*24*365, '/');

// RTL-Sprachen
$rtl = in_array($lang, ['ar','fa'], true);
$dir = $rtl ? 'rtl' : 'ltr';

// Texte (Einleitung + Infoblock je Sprache)
$t = [
  'de' => [
    'title' => 'Willkommen zur Online-Anmeldung – Sprachklassen',
    'lead'  => 'Dieses Angebot richtet sich an neu zugewanderte Menschen in Oldenburg. Das Formular hilft uns, Kontakt aufzunehmen und passende Angebote zu finden.',
    'bullets' => [
      'Halten Sie bitte Kontaktdaten und Ausweisdokumente bereit (falls vorhanden).',
      'Die Angaben können in mehreren Sprachen ausgefüllt werden.',
      'Ihre Daten werden gemäß DSGVO vertraulich behandelt.',
    ],
    'cta' => 'Weiter zum Formular',
    'info_p' => [
      'Liebe Schülerin, lieber Schüler,',
      'Hiermit bewerben Sie sich für einen Platz in der Sprachlernklasse „BES Sprache und Integration“ einer berufsbildenden Schule (BBS) in Oldenburg. Sie bewerben sich nicht für eine bestimmte BBS. Welche Schule Sie in der Sprachlernklasse aufnimmt, wird Ihnen nach dem 20. Februar mitgeteilt.',
      'Sie können nur unter folgenden Voraussetzungen aufgenommen werden:',
    ],
    'info_bullets' => [
      'Sie benötigen intensive Deutschförderung (Deutschkenntnisse unter B1).',
      'Sie sind zu Beginn des nächsten Schuljahres höchstens 3 Jahre in Deutschland.',
      'Sie sind am 30. September dieses Jahres mindestens 16 und höchstens 18 Jahre alt.',
      'Sie sind im nächsten Schuljahr schulpflichtig.',
    ],
  ],
  'en' => [
    'title' => 'Welcome to the Online Registration – Language Classes',
    'lead'  => 'This service is for newly arrived people in Oldenburg. The form helps us contact you and find suitable language class options.',
    'bullets' => [
      'Please have your contact details and ID/passport ready (if available).',
      'You can fill in the form in different languages.',
      'Your data is handled confidentially under GDPR.',
    ],
    'cta' => 'Go to the form',
    'info_p' => [
      'Dear student,',
      'With this application you are applying for a place in the language learning class “BES Language and Integration” at a vocational school (BBS) in Oldenburg. You are not applying to a specific BBS. After 20 February you will be informed which school will accept you into the class.',
      'You can be admitted only if all of the following conditions are met:',
    ],
    'info_bullets' => [
      'You need intensive German support (German level below B1).',
      'At the start of the next school year you have been in Germany for no more than 3 years.',
      'On 30 September of this year you are at least 16 and at most 18 years old.',
      'You are subject to compulsory schooling in the next school year.',
    ],
  ],
  'fr' => [
    'title' => 'Bienvenue – Inscription en ligne aux cours de langue',
    'lead'  => 'Ce service s’adresse aux personnes récemment arrivées à Oldenburg. Le formulaire nous aide à vous contacter et à proposer une offre adaptée.',
    'bullets' => [
      'Préparez vos coordonnées et un document d’identité (si disponible).',
      'Le formulaire peut être rempli dans plusieurs langues.',
      'Vos données sont traitées de manière confidentielle (RGPD).',
    ],
    'cta' => 'Aller au formulaire',
    'info_p' => [
      'Chère élève, cher élève,',
      'Par la présente, vous posez votre candidature pour une place dans la classe d’apprentissage de la langue « BES Langue et Intégration » d’un établissement professionnel (BBS) à Oldenburg. Vous ne candidatez pas pour un établissement précis. Après le 20 février, vous serez informé·e de l’établissement qui vous accueillera.',
      'Vous ne pouvez être admis·e que si toutes les conditions suivantes sont remplies :',
    ],
    'info_bullets' => [
      'Vous avez besoin d’un soutien intensif en allemand (niveau inférieur à B1).',
      'Au début de la prochaine année scolaire, vous êtes en Allemagne depuis au plus 3 ans.',
      'Au 30 septembre de cette année, vous avez au moins 16 ans et au plus 18 ans.',
      'Vous êtes soumis·e à l’obligation scolaire pour la prochaine année scolaire.',
    ],
  ],
  'uk' => [
    'title' => 'Ласкаво просимо до онлайн-реєстрації – мовні класи',
    'lead'  => 'Ця послуга для людей, які нещодавно прибули до Ольденбурга. Форма допоможе нам зв’язатися з вами та підібрати відповідні курси.',
    'bullets' => [
      'Підготуйте контактні дані та документ, що посвідчує особу (за наявності).',
      'Форму можна заповнювати різними мовами.',
      'Ваші дані обробляються конфіденційно відповідно до GDPR.',
    ],
    'cta' => 'Перейти до форми',
    'info_p' => [
      'Шановна ученице, шановний учню!',
      'Ви подаєте заявку на місце у класі вивчення мови «BES Мова та інтеграція» у професійній школі (BBS) міста Ольденбург. Ви не подаєтеся до конкретної школи. Після 20 лютого вам повідомлять, яка школа зарахує вас до класу.',
      'Вас можуть зарахувати лише за таких умов:',
    ],
    'info_bullets' => [
      'вам потрібна інтенсивна підтримка з німецької мови (рівень нижче B1);',
      'на початок наступного навчального року ви перебуваєте в Німеччині не більше 3 років;',
      'станом на 30 вересня цього року вам щонайменше 16 і не більше 18 років;',
      'у наступному навчальному році ви підлягаєте обов’язковому шкільному навчанню.',
    ],
  ],
  'ar' => [
    'title' => 'مرحبًا بكم في التسجيل الإلكتروني – صفوف اللغة',
    'lead'  => 'هذه الخدمة مخصّصة للوافدين الجدد إلى أولدنبورغ. يساعدنا النموذج على التواصل معكم واختيار الدورة المناسبة.',
    'bullets' => [
      'يرجى تجهيز بيانات الاتصال ووثيقة الهوية إن وُجدت.',
      'يمكن تعبئة النموذج بعدة لغات.',
      'تُعالج بياناتكم بسرية وفق اللائحة العامة لحماية البيانات (GDPR).',
    ],
    'cta' => 'الانتقال إلى النموذج',
    'info_p' => [
      'عزيزتي الطالبة، عزيزي الطالب،',
      'بهذا التقديم تتقدّم/ين للحصول على مقعد في صف تعلّم اللغة «BES اللغة والاندماج» في إحدى المدارس المهنية (BBS) في أولدنبورغ. لا تتقدّم/ين إلى مدرسة بعينها. بعد 20 فبراير سيتم إبلاغك بأي مدرسة ستقبلك في الصف.',
      'لا يمكن قبولك إلا إذا توفّرت الشروط التالية جميعها:',
    ],
    'info_bullets' => [
      'تحتاج/ين إلى دعم مكثّف في اللغة الألمانية (مستوى أقل من B1).',
      'عند بداية العام الدراسي القادم لا تتجاوز مدة إقامتك في ألمانيا 3 سنوات.',
      'في تاريخ 30 سبتمبر من هذا العام يكون عمرك بين 16 و18 عامًا.',
      'تكون/ين خاضعًا/ة للتعليم الإلزامي في العام الدراسي القادم.',
    ],
  ],
  'ru' => [
    'title' => 'Добро пожаловать на онлайн-регистрацию – языковые курсы',
    'lead'  => 'Сервис для недавно прибывших в Ольденбург. Эта форма помогает связаться с вами и подобрать подходящие варианты.',
    'bullets' => [
      'Подготовьте контактные данные и документ, удостоверяющий личность (если есть).',
      'Форму можно заполнить на разных языках.',
      'Ваши данные обрабатываются конфиденциально в соответствии с GDPR.',
    ],
    'cta' => 'Перейти к форме',
    'info_p' => [
      'Уважаемая ученица, уважаемый ученик!',
      'Этой заявкой вы подаётесь на место в языковом классе «BES Язык и интеграция» профессиональной школы (BBS) в Ольденбурге. Вы не подаётесь в конкретную школу. После 20 февраля вам сообщат, какая школа примет вас в класс.',
      'Поступить можно только при выполнении всех следующих условий:',
    ],
    'info_bullets' => [
      'вам требуется интенсивная поддержка по немецкому языку (уровень ниже B1);',
      'к началу следующего учебного года вы находитесь в Германии не более 3 лет;',
      'на 30 сентября текущего года вам не менее 16 и не более 18 лет;',
      'в следующем учебном году на вас распространяется обязанность школьного обучения.',
    ],
  ],
  'tr' => [
    'title' => 'Çevrimiçi Kayıt – Dil Kursları',
    'lead'  => 'Bu hizmet, Oldenburg’a yeni gelen kişiler içindir. Form, sizinle iletişim kurmamıza ve uygun kurs seçeneklerini bulmamıza yardımcı olur.',
    'bullets' => [
      'Lütfen iletişim bilgilerinizi ve kimlik belgenizi (varsa) hazırlayın.',
      'Formu birden fazla dilde doldurabilirsiniz.',
      'Verileriniz GDPR kapsamında gizli tutulur.',
    ],
    'cta' => 'Forma git',
    'info_p' => [
      'Sevgili öğrenci,',
      'Bu başvuru ile Oldenburg’daki bir mesleki okulda (BBS) “BES Dil ve Uyum” dil öğrenme sınıfına başvuruyorsunuz. Belirli bir BBS’e başvurmuyorsunuz. 20 Şubat’tan sonra hangi okulun sizi sınıfa kabul edeceği size bildirilecektir.',
      'Aşağıdaki koşulların tümü sağlandığında kabul edilebilirsiniz:',
    ],
    'info_bullets' => [
      'Yoğun Almanca desteğine ihtiyacınız var (Almanca seviyeniz B1’in altında).',
      'Gelecek öğretim yılının başlangıcında Almanya’da en fazla 3 yıldır bulunuyorsunuz.',
      'Bu yıl 30 Eylül tarihi itibarıyla yaşınız en az 16, en fazla 18’dir.',
      'Gelecek öğretim yılında okul zorunluluğuna tabisiniz.',
    ],
  ],
  'fa' => [
    'title' => 'ثبت‌نام آنلاین – کلاس‌های زبان',
    'lead'  => 'این خدمت برای افراد تازه‌وارد به اولدن‌بورگ است. این فرم به ما کمک می‌کند با شما تماس بگیریم و گزینه‌های مناسب را بیابیم.',
    'bullets' => [
      'لطفاً اطلاعات تماس و در صورت امکان مدرک هویتی را آماده داشته باشید.',
      'می‌توانید فرم را به چند زبان تکمیل کنید.',
      'داده‌های شما مطابق مقررات GDPR محرمانه نگه‌داری می‌شود.',
    ],
    'cta' => 'ورود به فرم',
    'info_p' => [
      'دانش‌آموز گرامی،',
      'با این درخواست برای یک جایگاه در کلاس یادگیری زبان «BES زبان و ادغام» در یکی از مدارس فنی‌وحرفه‌ای (BBS) اولدن‌بورگ اقدام می‌کنید. شما برای یک مدرسه مشخص اقدام نمی‌کنید. پس از ۲۰ فوریه به شما اطلاع داده می‌شود که کدام مدرسه شما را در کلاس می‌پذیرد.',
      'پذیرش تنها در صورت برآورده شدن همه شرایط زیر ممکن است:',
    ],
    'info_bullets' => [
      'به پشتیبانی فشرده زبان آلمانی نیاز دارید (سطح زیر B1).',
      'در آغاز سال تحصیلی آینده حداکثر ۳ سال است که در آلمان هستید.',
      'در تاریخ ۳۰ سپتامبر امسال سن شما حداقل ۱۶ و حداکثر ۱۸ سال است.',
      'در سال تحصیلی آینده مشمول تحصیل اجباری هستید.',
    ],
  ],
];

$text = $t[$lang] ?? $t['de'];

// Hilfsfunktion
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?= h($lang) ?>" dir="<?= $dir ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($text['title']) ?></title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
  <style>
    .lang-switch { gap: .5rem; }
    .card { border-radius: 1rem; }
    <?php if ($rtl): ?>body { text-align: right; }<?php endif; ?>
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <!-- Sprachwahl -->
    <div class="d-flex lang-switch justify-content-end mb-3">
      <form method="get" action="" class="d-flex lang-switch">
        <label class="me-2 fw-semibold" for="lang">Sprache / Language:</label>
        <select class="form-select form-select-sm" name="lang" id="lang" onchange="this.form.submit()" style="max-width: 220px;">
          <?php foreach ($languages as $code => $label): ?>
            <option value="<?= h($code) ?>" <?= $code===$lang?'selected':''; ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-sm btn-primary ms-2">OK</button></noscript>
      </form>
    </div>

    <div class="card shadow border-0">
      <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-3"><?= h($text['title']) ?></h1>
        <p class="lead mb-4"><?= h($text['lead']) ?></p>
        <ul class="mb-4">
          <?php foreach ($text['bullets'] as $li): ?><li><?= h($li) ?></li><?php endforeach; ?>
        </ul>

        <!-- Infoblock je Sprache -->
        <div class="alert alert-info mb-4">
          <?php foreach (($text['info_p'] ?? []) as $p): ?>
            <p class="mb-2"><?= h($p) ?></p>
          <?php endforeach; ?>
          <?php if (!empty($text['info_bullets'])): ?>
            <ul class="mb-0">
              <?php foreach ($text['info_bullets'] as $li): ?><li><?= h($li) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

        <a href="/form_personal.php" class="btn btn-primary">
          <?= h($text['cta']) ?>
        </a>
      </div>
    </div>
  </div>

  <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
  <?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
