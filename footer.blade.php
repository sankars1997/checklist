
<style>
.footer {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    color: #6c757d;
    box-shadow: 0 -1px 4px rgba(0, 0, 0, 0.05);
}

.bg-light-custom {
    background-color: #f2f4f7 !important; /* Light background */
}

.footer a {
    text-decoration: none;
    transition: all 0.3s ease;
}

/* Social icons with fixed brand colors */
.social-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #fff;
    font-size: 0.9rem;
}

/* Brand colors applied directly */
.text-twitter { background-color: #1DA1F2; }
.text-facebook { background-color: #1877F2; }
.text-linkedin { background-color: #0A66C2; }
.text-instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285AEB 90%); }

/* Hover effect: slight lift */
.social-icon:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

@media (max-width: 768px) {
    .footer { font-size: 0.8rem; }
    .social-icon { width: 28px; height: 28px; font-size: 0.8rem; }
}





</style>
<!-- Load Font Awesome -->
<!-- Load Font Awesome -->
<!-- Load Font Awesome -->
<!-- Load Font Awesome -->
<!-- Load Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<footer class="footer bg-light-custom text-center text-muted py-3 shadow-sm">
  <div class="container">
    <!-- Text Section -->
    <div class="row justify-content-center mb-1">
      <div class="col-auto">
        <p class="mb-0 small text-secondary">
          © {{ date('Y') }} 
          <a href="http://www.cee-kerala.org" target="_blank" class="text-decoration-none fw-bold text-primary">
            CEE KERALA
          </a>
        </p>
        <p class="mb-0 small text-secondary">
          Managed by <span class="fw-semibold"><a href="cee.kerala.gov.in">Commissioner Of Entrance Examinations (CEE) IT</a></span>
        </p>
      </div>
    </div>

    <!-- Social Icons -->
    <div class="row justify-content-center mb-1">
      <div class="col-auto">
        <div class="d-flex justify-content-center gap-2">
          <a href="" target="_blank" class="social-icon text-twitter">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="" target="_blank" class="social-icon text-facebook">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="" target="_blank" class="social-icon text-linkedin">
            <i class="fab fa-linkedin-in"></i>
          </a>
          <a href="" target="_blank" class="social-icon text-instagram">
            <i class="fab fa-instagram"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Date & Time -->
    <div class="row justify-content-center">
      <div class="col-auto">
        <p class="mb-0 small text-secondary" id="currentDateTime"></p>
      </div>
    </div>
  </div>
</footer>
