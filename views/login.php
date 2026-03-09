<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap');

    /* ========== Auth pages – Modern Container Design ========== */
    .auth-page {
      min-height: 100vh;
      position: relative;
      background: #f8fafc;
      font-family: 'Outfit', sans-serif;
    }

    /* Background Video Support */
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

    .auth-page-inner {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .auth-logo-wrap {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      align-items: center;
    }

    .auth-logo {
      height: 60px;
      width: auto;
    }

    @media (max-width: 1023px) {
      .auth-logo-wrap {
        padding: 1rem 1.5rem;
      }
    }

    /* Card: Compact white container */
    .auth-card {
      width: 100%;
      max-width: 420px;
      padding: 1.75rem 2rem;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
      border: 1px solid #e2e8f0;
    }

    .auth-card--wide {
      max-width: 560px;
    }

    .auth-back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: #64748b;
      text-decoration: none;
      font-size: 0.8125rem;
      font-weight: 600;
      margin-bottom: 1.25rem;
      transition: all 0.2s ease;
      padding: 0.4rem 0.6rem;
      border-radius: 6px;
      background: #f1f5f9;
    }

    .auth-back-link:hover {
      color: #2b52a5;
      background: #e0e7ff;
      transform: translateX(-3px);
    }

    .auth-card-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .auth-icon-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      margin-bottom: 0.75rem;
      color: #2b52a5;
      background: rgba(43, 82, 165, 0.1);
      border-radius: 12px;
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

    /* Form container styles */
    .auth-card form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .auth-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .auth-card form label {
      display: block;
      font-weight: 600;
      font-size: 0.8125rem;
      color: #334155;
    }

    .auth-card form input {
      width: 100%;
      margin-top: 0.35rem;
      padding: 0.6rem 0.875rem;
      border: 1.5px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.9375rem;
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
      padding: 0.75rem 1rem;
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
    }

    .auth-remember {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0;
      font-size: 0.875rem;
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
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid #f1f5f9;
      text-align: center;
      color: #64748b;
      font-size: 0.8125rem;
    }

    .auth-footer a {
      color: #2b52a5;
      font-weight: 700;
    }

    .auth-error {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      padding: 0.6rem 0.875rem;
      border-radius: 6px;
      font-size: 0.8125rem;
      margin-bottom: 0.75rem;
    }

    @media (max-width: 640px) {
      .auth-form-grid { grid-template-columns: 1fr; }
      .auth-card--wide { max-width: 420px; }
    }
</style>

<div class="auth-page">
    <div class="landing-video-wrap">
        <video id="auth-video" class="landing-video" src="/public/images/landing.mp4" autoplay muted loop playsinline aria-hidden="true"></video>
        <div class="landing-video-overlay" aria-hidden="true"></div>
    </div>

    <div class="auth-logo-wrap">
        <a href="<?= url('landing') ?>">
            <img src="/public/images/logo1.png" alt="FH CRM" class="auth-logo" />
        </a>
    </div>

    <div class="auth-page-inner">
        <div class="auth-card">
            <a href="<?= url('landing') ?>" class="auth-back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Home
            </a>
            
            <div class="auth-card-header">
                <div class="auth-icon-wrap" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account</p>
            </div>

            <?php if (!empty($flashError)): ?>
                <div class="auth-error"><?= h($flashError) ?></div>
            <?php endif; ?>

            <form action="<?= url('login') ?>" method="POST">
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
                Don't have an account? <a href="<?= url('register') ?>">Create Account</a>
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('auth-video');
        if (video) {
            video.playbackRate = 0.5;
            video.play().catch(() => {});
        }
    });
</script>
