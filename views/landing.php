<style>
    .landing-new {
      background: #0f172a;
      min-height: 100vh;
      color: white;
      font-family: 'Outfit', sans-serif;
      position: relative;
    }
    .landing-content {
      transition: filter 0.3s ease;
    }
    .landing-content.blurred {
      filter: blur(3px);
      pointer-events: none;
    }
    .landing-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem 2rem;
      background: linear-gradient(to bottom, rgba(15, 23, 42, 0.8), transparent);
      backdrop-filter: blur(8px);
    }
    .landing-logo {
      height: 60px;
      width: auto;
    }
    .landing-nav {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .landing-nav-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.625rem 1.25rem;
      background: white;
      color: #0f172a;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 0.875rem;
      text-decoration: none;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
    }
    .landing-nav-btn:hover {
      background: #f1f5f9;
      transform: translateY(-1px);
    }
    .landing-nav-btn--outline {
      background: transparent;
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .landing-nav-btn--outline:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: white;
    }
    .landing-hero-full {
      position: relative;
      height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .landing-video-wrap {
      position: absolute;
      inset: 0;
      z-index: 0;
    }
    .landing-video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .landing-video-overlay {
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at center, rgba(15, 23, 42, 0.4) 0%, rgba(15, 23, 42, 0.9) 100%);
    }
    .hero-content {
      position: relative;
      z-index: 10;
      max-width: 56rem;
      text-align: center;
      padding: 0 1.5rem;
    }
    .hero-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      background: rgba(43, 82, 165, 0.1);
      border: 1px solid rgba(43, 82, 165, 0.2);
      color: #9ab3f7;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 1.5rem;
    }
    .hero-title {
      font-size: 3.5rem;
      line-height: 1.1;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 1.5rem;
    }
    @media (min-width: 768px) {
      .hero-title {
        font-size: 5rem;
      }
    }
    .text-gradient {
      background: linear-gradient(to bottom right, #ffffff 30%, #94a3b8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-subtitle {
      font-size: 1.125rem;
      color: #94a3b8;
      max-width: 32rem;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* Auth Overlay Styles */
    .auth-overlay {
      position: fixed;
      inset: 0;
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      background: rgba(15, 23, 42, 0.4);
    }
    .auth-overlay.active {
      display: flex;
    }

    /* Auth Card Styles (unmodified design) */
    .auth-card {
      width: 100%;
      max-width: 500px;
      padding: 1.75rem 2rem;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 16px;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15), 0 8px 16px -8px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      position: relative;
      color: #0f172a;
      display: none;
      margin-top: 50px;
    }
    .auth-card.active {
      display: block;
    }
    .auth-card--wide {
      max-width: 450px;
    }
    .auth-card-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .auth-title {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
    }
    .auth-subtitle {
      color: #64748b;
      margin: 0.25rem 0 0;
      font-size: 0.875rem;
      font-weight: 500;
    }
    .auth-card form {
      display: flex;
      flex-direction: column;
      gap: 0.50rem;
    }
    .auth-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .auth-card form label {
      display: block;
      font-weight: 600;
      font-size: 0.95rem;
      color: #334155;
      text-align: left;
    }
    .auth-card form input {
      width: 100%;
      font-weight: 300;
      margin-top: 0.35rem;
      padding: 0.5rem 0.75rem;
      border: 1.5px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.80rem;
      color: #0f172a;
      background: #f8fafc;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }
    .auth-card form input:focus {
      outline: none;
      border-color: #2b52a5;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(43, 82, 165, 0.1);
    }
    .auth-card form button {
      width: 100%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.5rem 0.75rem;
      margin-top: 0.5rem;
      background: #2b52a5;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 0.9375rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .auth-card form button:hover {
      background: #9ab3f7;
      transform: translateY(-1px);
    }
    .auth-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 0.25rem;
      font-size: 0.8125rem;
    }
    .auth-remember {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0;
      font-size: 0.8125rem !important;
      color: #64748b;
      cursor: pointer;
      font-weight: 500;
      line-height: 1;
      vertical-align: middle;
      white-space: nowrap;
    }
    .auth-remember input {
      width: auto !important;
      height: 1rem;
      accent-color: #2b52a5;
      cursor: pointer;
      margin: 0 !important;
      vertical-align: middle;
      position: relative;
      top: -1px;
    }
    .auth-forgot {
      font-size: 0.8125rem;
      color: #2b52a5;
      text-decoration: none;
      font-weight: 600;
    }
    .auth-footer {
      margin-top: 1rem;
      padding-top: 0.3rem;
      border-top: 1px solid #f1f5f9;
      text-align: center;
      color: #64748b;
      font-size: 0.8125rem;
    }
    .auth-footer a {
      color: #2b52a5;
      font-weight: 700;
      cursor: pointer;
    }
    .auth-error {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      padding: 0.6rem 0.875rem;
      border-radius: 6px;
      font-size: 0.8125rem;
      margin-bottom: 0.75rem;
      text-align: left;
    }
    .close-overlay {
      position: absolute;
      top: 1rem;
      right: 1rem;
      color: #64748b;
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 0.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }
    .close-overlay:hover {
      color: #0f172a;
    }
    @media (max-width: 640px) {
      .auth-form-grid { grid-template-columns: 1fr; }
      .auth-card--wide { max-width: 420px; }
    }

    .auth-icon-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      margin-bottom: 0.50rem;
      color: #2b52a5;
      background: rgba(43, 82, 165, 0.1);
      border-radius: 12px;
    }
</style>

<div class="landing-new">
  <div id="landing-content" class="landing-content">
    <header class="landing-header">
      <a href="<?= url('landing') ?>" class="landing-logo-link">
        <img src="/public/images/logo1.png" alt="FH CRM" class="landing-logo" />
      </a>
      <nav class="landing-nav">
        <a id="signin-trigger" href="<?= url('login') ?>" class="landing-nav-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Sign In
        </a>
      </nav>
    </header>

    <main>
      <section class="landing-hero-full">
        <div class="landing-video-wrap">
          <video
            id="hero-video"
            class="landing-video"
            src="/public/images/landing.mp4"
            autoplay
            muted
            loop
            playsinline
            aria-hidden="true"
          ></video>
          <div class="landing-video-overlay" aria-hidden="true"></div>
        </div>
        
        <div class="hero-content">
          <p class="hero-badge">Company Use Only</p>
          <h1 class="hero-title">
            <span class="text-gradient">Email</span> & <span class="text-gradient">SMS</span><br />
            Campaigns
          </h1>
          <p class="hero-subtitle">
            Sign in to access your company account. Manage contacts, send campaigns, and track results
          </p>
        </div>
      </section>
    </main>
  </div>

  <div id="auth-overlay" class="auth-overlay">
    <!-- Login Card -->
    <div class="auth-card auth-card--wide" id="login-card">
      <button class="close-overlay" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
      <div class="auth-card-header">
          <div class="auth-icon-wrap" aria-hidden="true">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
          <h1 class="auth-title">Login</h1>
          <p class="auth-subtitle">Sign in to your account</p>
      </div>

      <?php if (!empty($flashError) && currentPage() === 'landing' && ($_POST['action'] ?? '') === 'login'): ?>
          <div class="auth-error"><?= h($flashError) ?></div>
      <?php endif; ?>

      <form action="<?= url('landing') ?>" method="POST">
          <input type="hidden" name="action" value="login">
          <label>
              Email Address
              <input type="email" name="email" placeholder="name@company.com" required autocomplete="email" />
          </label>
          
          <label>
              Password
              <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
          </label>

          <div class="auth-options">
              <label class="auth-remember">
                  <input type="checkbox" name="remember_me">
                  <span>Remember me</span>
              </label>
              <a href="#" class="auth-forgot">Forgot password?</a>
          </div>

          <button type="submit">Sign In</button>
      </form>

      <p class="auth-footer">
          Don't have an account? <a id="switch-to-signup">Create Account</a>
      </p>
    </div>

    <!-- Register Card -->
    <div class="auth-card auth-card--wide" id="register-card">
        <button class="close-overlay" aria-label="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="auth-card-header">
          <div class="auth-icon-wrap" aria-hidden="true">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join our marketing platform today</p>
        </div>

        <?php if (!empty($flashError) && currentPage() === 'landing' && ($_POST['action'] ?? '') === 'register'): ?>
            <div class="auth-error"><?= h($flashError) ?></div>
        <?php endif; ?>

        <form action="<?= url('landing') ?>" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="auth-form-grid">
                <label style="grid-column: span 2;">
                    Full Name
                    <input type="text" name="name" placeholder="John Doe" required />
                    Work Email
                    <input type="email" name="email" placeholder="john@company.com" required autocomplete="email" />
                     Company Name
                    <input type="text" name="company_name" placeholder="Company Inc." required />
                </label>
                <label>
                    Password
                   <input type="password" name="password" placeholder="••••••••" required autocomplete="new-password" minlength="8" />
                </label>
                <label>
                  Confirm Password
                    <input type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
                </label>
            </div>
            <button type="submit">Create Account</button>
        </form>
        <p class="auth-footer">
            Already have an account? <a id="switch-to-login">Sign in</a>
        </p>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('hero-video');
        if (video) {
            video.playbackRate = 0.5;
            const playVideo = () => {
                video.play().catch(error => {
                    // Autoplay prevented
                });
            };
            playVideo();
            video.addEventListener('loadeddata', playVideo);
        }

        const signinTrigger = document.getElementById('signin-trigger');
        const signupTrigger = document.getElementById('signup-trigger');
        const authOverlay = document.getElementById('auth-overlay');
        const landingContent = document.getElementById('landing-content');
        const loginCard = document.getElementById('login-card');
        const registerCard = document.getElementById('register-card');
        const switchToSignup = document.getElementById('switch-to-signup');
        const switchToLogin = document.getElementById('switch-to-login');
        const closeBtns = document.querySelectorAll('.close-overlay');

        const openAuth = (type) => {
          authOverlay.classList.add('active');
          landingContent.classList.add('blurred');
          document.body.style.overflow = 'hidden';
          
          if (type === 'login') {
            loginCard.classList.add('active');
            registerCard.classList.remove('active');
          } else {
            registerCard.classList.add('active');
            loginCard.classList.remove('active');
          }
        };

        const closeAuth = () => {
          authOverlay.classList.remove('active');
          landingContent.classList.remove('blurred');
          document.body.style.overflow = '';
          loginCard.classList.remove('active');
          registerCard.classList.remove('active');
        };

        signinTrigger.addEventListener('click', (e) => {
          e.preventDefault();
          openAuth('login');
        });

        if (signupTrigger) {
          signupTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            openAuth('register');
          });
        }

        switchToSignup.addEventListener('click', () => openAuth('register'));
        switchToLogin.addEventListener('click', () => openAuth('login'));

        closeBtns.forEach(btn => btn.addEventListener('click', closeAuth));

        authOverlay.addEventListener('click', (e) => {
          if (e.target === authOverlay) {
            closeAuth();
          }
        });

        // Keep overlay open if there's a flash error
        <?php if (!empty($flashError) && currentPage() === 'landing'): ?>
          <?php if (($_POST['action'] ?? '') === 'login'): ?>
            openAuth('login');
          <?php elseif (($_POST['action'] ?? '') === 'register'): ?>
            openAuth('register');
          <?php endif; ?>
        <?php endif; ?>
    });
</script>
