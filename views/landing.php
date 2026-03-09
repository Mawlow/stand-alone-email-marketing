<style>
    .landing-new {
      background: #0f172a;
      min-height: 100vh;
      color: white;
      font-family: 'Outfit', sans-serif;
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
    }
    .landing-nav-btn:hover {
      background: #f1f5f9;
      transform: translateY(-1px);
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
</style>

<div class="landing-new">
  <header class="landing-header">
    <a href="<?= url('landing') ?>" class="landing-logo-link">
      <img src="/public/images/logo1.png" alt="FH CRM" class="landing-logo" />
    </a>
    <nav class="landing-nav">
      <a href="<?= url('login') ?>" class="landing-nav-btn">
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
    });
</script>
