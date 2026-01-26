<?php
// public/index.php
// Mehrsprachiger Einstieg + 3 Buttons (ohne E-Mail / mit E-Mail / Zugriff)
// Hinweis: Button "Zugang mit E-Mail erstellen" umbenannt zu "Mit E-Mail fortfahren"
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php'; // Sessions, h(), etc.

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

// Sprache ermitteln (Query > Cookie > Browser > de)
$lang = strtolower((string)($_GET['lang'] ?? ($_COOKIE['lang'] ?? '')));
if (!array_key_exists($lang, $languages)) {
  $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
  foreach ($languages as $code => $label) {
    if ($code !== '' && strpos($accept, $code) !== false) {
      $lang = $code;
      break;
    }
  }
  if (!array_key_exists($lang, $languages)) $lang = 'de';
}
setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/');

// RTL-Sprachen
$rtl = in_array($lang, ['ar','fa'], true);
$dir = $rtl ? 'rtl' : 'ltr';

// Texte (Einleitung + Infoblock + Buttons je Sprache)
$t = [
  'de' => [
    'title' => 'Willkommen zur Online-Anmeldung – Sprachklassen',
    'lead'  => 'Dieses Angebot richtet sich an neu zugewanderte Menschen in Oldenburg. Das Formular hilft uns, Kontakt aufzunehmen und passende Angebote zu finden.',
    'bullets' => [
      'Halten Sie bitte Kontaktdaten und Ausweisdokumente bereit (falls vorhanden).',
      'Die Angaben können in mehreren Sprachen ausgefüllt werden.',
      'Ihre Daten werden gemäß DSGVO vertraulich behandelt.',
    ],
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
    'access_title' => 'Datenschutz & Zugang',
    'access_intro' => 'Sie können mit oder ohne E-Mail-Adresse fortfahren. Der Zugriff auf gespeicherte Bewerbungen ist nur mit persönlichem Zugangscode (Token) und Geburtsdatum möglich.',
    'access_points' => [
      '<strong>Mit E-Mail:</strong> Sie erhalten einen Bestätigungscode und können mehrere Bewerbungen anlegen und später wieder aufrufen.',
      '<strong>Ohne E-Mail:</strong> Sie erhalten einen persönlichen Zugangscode (Access-Token). Bitte notieren/fotografieren Sie diesen – ohne verifizierte E-Mail ist keine Wiederherstellung möglich.',
    ],
    'btn_noemail'   => 'Ohne E-Mail fortfahren',
    'btn_create'    => 'Mit E-Mail fortfahren',
    'btn_load'      => 'Zugriff auf Bewerbung/en',
  ],
  'en' => [
    'title' => 'Welcome to the Online Registration – Language Classes',
    'lead'  => 'This service is for newly arrived people in Oldenburg. The form helps us contact you and find suitable language class options.',
    'bullets' => [
      'Please have your contact details and ID/passport ready (if available).',
      'You can fill in the form in different languages.',
      'Your data is handled confidentially under GDPR.',
    ],
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
    'access_title' => 'Privacy & Access',
    'access_intro' => 'You can proceed with or without an email address. Access to saved applications is only possible with your personal access token and date of birth.',
    'access_points' => [
      '<strong>With email:</strong> You receive a verification code and can create multiple applications and access them later.',
      '<strong>Without email:</strong> You get a personal access token. Please write it down or take a photo—without a verified email there is no recovery.',
    ],
    'btn_noemail' => 'Proceed without email',
    'btn_create'  => 'Continue with email',
    'btn_load'    => 'Access your application(s)',
  ],
'fr' => [
    'title' => 'Bienvenue – Inscription en ligne aux cours de langue',
    'lead'  => 'Ce service s’adresse aux personnes récemment arrivées à Oldenburg. Le formulaire nous aide à vous contacter et à proposer une offre adaptée.',
    'bullets' => [
      'Préparez vos coordonnées et un document d’identité (si disponible).',
      'Le formulaire peut être rempli dans plusieurs langues.',
      'Vos données sont traitées de manière confidentielle (RGPD).',
    ],
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
    'access_title' => 'Confidentialité & Accès',
    'access_intro' => 'Vous pouvez continuer avec ou sans adresse e-mail. L’accès aux candidatures enregistrées n’est possible qu’avec votre jeton d’accès personnel et votre date de naissance.',
    'access_points' => [
      '<strong>Avec e-mail :</strong> vous recevez un code de vérification et pouvez reprendre votre candidature plus tard.',
      '<strong>Sans e-mail :</strong> vous recevez un jeton d’accès personnel. Veuillez le noter/photographier — sans e-mail vérifié, aucune récupération n’est possible.',
    ],
    'btn_noemail' => 'Continuer sans e-mail',
    'btn_create'  => 'Créer un accès avec e-mail',
    'btn_load'    => 'Charger la candidature',
  ],
  'uk' => [
    'title' => 'Ласкаво просимо до онлайн-реєстрації – мовні класи',
    'lead'  => 'Ця послуга для людей, які нещодавно прибули до Ольденбурга. Форма допоможе нам зв’язатися з вами та підібрати відповідні курси.',
    'bullets' => [
      'Підготуйте контактні дані та документ, що посвідчує особу (за наявності).',
      'Форму можна заповнювати різними мовами.',
      'Ваші дані обробляються конфіденційно відповідно до GDPR.',
    ],
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
    'access_title' => 'Конфіденційність та доступ',
    'access_intro' => 'Ви можете продовжити з електронною поштою або без неї. Доступ до збережених заяв можливий лише за допомогою особистого токена доступу та дати народження.',
    'access_points' => [
      '<strong>З e-mail:</strong> ви отримаєте код підтвердження і зможете пізніше продовжити заповнення.',
      '<strong>Без e-mail:</strong> ви отримаєте особистий токен доступу. Занотуйте/сфотографуйте його — без підтвердженої e-mail відновлення неможливе.',
    ],
    'btn_noemail' => 'Продовжити без e-mail',
    'btn_create'  => 'Створити доступ за e-mail',
    'btn_load'    => 'Завантажити заявку',
  ],
  'ar' => [
    'title' => 'مرحبًا بكم في التسجيل الإلكتروني – صفوف اللغة',
    'lead'  => 'هذه الخدمة مخصّصة للوافدين الجدد إلى أولدنبورغ. يساعدنا النموذج على التواصل معكم واختيار الدورة المناسبة.',
    'bullets' => [
      'يرجى تجهيز بيانات الاتصال ووثيقة الهوية إن وُجدت.',
      'يمكن تعبئة النموذج بعدة لغات.',
      'تُعالج بياناتكم بسرية وفق اللائحة العامة لحماية البيانات (GDPR).',
    ],
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
    'access_title' => 'الخصوصية والوصول',
    'access_intro' => 'يمكنك المتابعة مع عنوان بريد إلكتروني أو بدونه. لا يمكن الوصول إلى الطلبات المحفوظة إلا باستخدام رمز الوصول الشخصي وتاريخ الميلاد.',
    'access_points' => [
      '<strong>مع البريد الإلكتروني:</strong> ستتلقى رمز تحقق ويمكنك متابعة طلبك لاحقًا.',
      '<strong>بدون بريد إلكتروني:</strong> ستحصل على رمز وصول شخصي. يُرجى حفظه/تصويره — بدون بريد إلكتروني مُوثَّق لا يمكن الاستعادة.',
    ],
    'btn_noemail' => 'المتابعة دون بريد إلكتروني',
    'btn_create'  => 'إنشاء وصول عبر البريد',
    'btn_load'    => 'تحميل الطلب',
  ],
  'ru' => [
    'title' => 'Добро пожаловать на онлайн-регистрацию – языковые курсы',
    'lead'  => 'Сервис для недавно прибывших в Ольденбург. Эта форма помогает связаться с вами и подобрать подходящие варианты.',
    'bullets' => [
      'Подготовьте контактные данные и документ, удостоверяющий личность (если есть).',
      'Форму можно заполнить на разных языках.',
      'Ваши данные обрабатываются конфиденциально в соответствии с GDPR.',
    ],
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
    'access_title' => 'Конфиденциальность и доступ',
    'access_intro' => 'Можно продолжить с электронной почтой или без неё. Доступ к сохранённым заявлениям возможен только с личным токеном доступа и датой рождения.',
    'access_points' => [
      '<strong>С e-mail:</strong> вы получите код подтверждения и сможете позже продолжить.',
      '<strong>Без e-mail:</strong> вы получите личный токен доступа. Запишите/сфотографируйте его — без подтверждённого e-mail восстановление невозможно.',
    ],
    'btn_noemail' => 'Продолжить без e-mail',
    'btn_create'  => 'Создать доступ через e-mail',
    'btn_load'    => 'Загрузить заявление',
  ],
  'tr' => [
    'title' => 'Çevrimiçi Kayıt – Dil Kursları',
    'lead'  => 'Bu hizmet, Oldenburg’a yeni gelen kişiler içindir. Form, sizinle iletişim kurmamıza ve uygun kurs seçeneklerini bulmamıza yardımcı olur.',
    'bullets' => [
      'Lütfen iletişim bilgilerinizi ve kimlik belgenizi (varsa) hazırlayın.',
      'Formu birden fazla dilde doldurabilirsiniz.',
      'Verileriniz GDPR kapsamında gizli tutulur.',
    ],
    'info_p' => [
      'Sevgili öğrenci,',
      'Bu başvuru ile Oldenburg’daki bir mesleki okulda (BBS) “BES Dil ve Uyum” dil öğrenme sınıfına başvuruyorsunuz. Belirli bir BBS’e başvurmuyorsunuz. 20 Şubat’tan sonra hangi okulun sizi kabul edeceği bildirilecektir.',
      'Aşağıdaki koşulların tümü sağlandığında kabul edilebilirsiniz:',
    ],
    'info_bullets' => [
      'Yoğun Almanca desteğine ihtiyacınız var (Almanca seviyeniz B1’in altında).',
      'Gelecek öğretim yılının başlangıcında Almanya’da en fazla 3 yıldır bulunuyorsunuz.',
      'Bu yıl 30 Eylül tarihi itibarıyla yaşınız en az 16, en fazla 18’dir.',
      'Gelecek öğretim yılında okul zorunluluğuna tabisiniz.',
    ],
    'access_title' => 'Gizlilik ve Erişim',
    'access_intro' => 'E-posta ile veya e-posta olmadan devam edebilirsiniz. Kaydedilmiş başvurulara erişim yalnızca kişisel erişim kodu ve doğum tarihi ile mümkündür.',
    'access_points' => [
      '<strong>E-postayla:</strong> Doğrulama kodu alır ve başvurunuza daha sonra devam edebilirsiniz.',
      '<strong>E-posta olmadan:</strong> Kişisel bir erişim kodu alırsınız. Lütfen not edin/fotoğraflayın — doğrulanmış e-posta olmadan kurtarma yoktur.',
    ],
    'btn_noemail' => 'E-posta olmadan devam et',
    'btn_create'  => 'E-posta ile erişim oluştur',
    'btn_load'    => 'Başvuruyu yükle',
  ],
  'fa' => [
    'title' => 'ثبت‌نام آنلاین – کلاس‌های زبان',
    'lead'  => 'این خدمت برای افراد تازه‌وارد به اولدن‌بورگ است. این فرم به ما کمک می‌کند با شما تماس بگیریم و گزینه‌های مناسب را بیابیم.',
    'bullets' => [
      'لطفاً اطلاعات تماس و در صورت امکان مدرک هویتی را آماده داشته باشید.',
      'می‌توانید فرم را به چند زبان تکمیل کنید.',
      'داده‌های شما مطابق مقررات GDPR محرمانه نگه‌داری می‌شود.',
    ],
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
    'access_title' => 'حریم خصوصی و دسترسی',
    'access_intro' => 'می‌توانید با ایمیل یا بدون آن ادامه دهید. دسترسی به درخواست‌های ذخیره‌شده فقط با کُد دسترسی شخصی و تاریخ تولد امکان‌پذیر است.',
    'access_points' => [
      '<strong>با ایمیل:</strong> یک کُد تأیید دریافت می‌کنید و می‌توانید بعداً ادامه دهید.',
      '<strong>بدون ایمیل:</strong> یک کُد دسترسی شخصی دریافت می‌کنید. لطفاً آن را یادداشت/تصویر کنید — بدون ایمیل تأیید شده، بازیابی ممکن نیست.',
    ],
    'btn_noemail' => 'ادامه بدون ایمیل',
    'btn_create'  => 'ایجاد دسترسی با ایمیل',
    'btn_load'    => 'بارگذاری درخواست',
  ],
];

$text = $t[$lang] ?? $t['de'];

// Seitentitel & HTML-Parameter für Header
$title     = (string)($text['title'] ?? 'Online-Anmeldung');
$html_lang = $lang;
$html_dir  = $dir;

// Header: allgemeiner Seiten-Header + App-Topbar (Status/Token)
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>
<style>
  .lang-switch { gap: .5rem; }
  .card { border-radius: 1rem; }
  <?php if ($rtl): ?> body { text-align: right; } <?php endif; ?>
</style>

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
      <h1 class="h3 mb-3"><?= h((string)($text['title'] ?? '')) ?></h1>
      <p class="lead mb-4"><?= h((string)($text['lead'] ?? '')) ?></p>

      <?php if (!empty($text['bullets'])): ?>
        <ul class="mb-4">
          <?php foreach ((array)$text['bullets'] as $li): ?>
            <li><?= h((string)$li) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <!-- Infoblock (Voraussetzungen) -->
      <?php if (!empty($text['info_p']) || !empty($text['info_bullets'])): ?>
        <div class="alert alert-info mb-4">
          <?php foreach ((array)($text['info_p'] ?? []) as $p): ?>
            <p class="mb-2"><?= h((string)$p) ?></p>
          <?php endforeach; ?>

          <?php if (!empty($text['info_bullets'])): ?>
            <ul class="mb-0">
              <?php foreach ((array)$text['info_bullets'] as $li): ?>
                <li><?= h((string)$li) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Zugang/DSGVO -->
      <div class="alert alert-secondary mb-4">
        <h2 class="h5 mb-2"><?= h((string)($text['access_title'] ?? '')) ?></h2>
        <p class="mb-2"><?= h((string)($text['access_intro'] ?? '')) ?></p>

        <?php if (!empty($text['access_points'])): ?>
          <ul class="mb-0">
            <?php foreach ((array)$text['access_points'] as $li): ?>
              <li><?= $li /* enthält <strong> bewusst unge-escaped */ ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- Aktionen -->
      <div class="d-flex flex-column flex-md-row gap-2">
        <a href="/form_personal.php?mode=noemail" class="btn btn-primary flex-fill">
          <?= h((string)($text['btn_noemail'] ?? '')) ?>
        </a>

        <a href="/access_create.php" class="btn btn-outline-primary flex-fill">
          <?= h((string)($text['btn_create'] ?? '')) ?>
        </a>

        <a href="/access_login.php" class="btn btn-outline-secondary flex-fill">
          <?= h((string)($text['btn_load'] ?? '')) ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
