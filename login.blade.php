<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checklist Login</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* Page Background */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

    background: linear-gradient(135deg,#3f51b5,#1a237e);

    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Login Card */
.login-box {
    width: 380px;
    padding: 40px 30px;

    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    border-radius: 14px;

    box-shadow: 0 10px 30px rgba(0,0,0,0.35);

    color: #fff;
    text-align: center;
}

/* Heading */
.portal-title{
    font-size:18px;
    font-weight:600;
    margin-bottom:5px;
}

.portal-sub{
    font-size:13px;
    margin-bottom:15px;
}

.login-box h3{
    font-weight:700;
    margin-bottom:25px;
}

/* Form Inputs */
.login-box .form-control {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
    font-weight: 500;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.login-box .form-control::placeholder {
    color: rgba(255,255,255,0.7);
}

.login-box .form-control:focus {
    outline: none;
    border-color: #fff;
    box-shadow: 0 0 8px rgba(255,255,255,0.4);
}

/* Floating labels */
.float-label {
    position: absolute;
    left: 15px;
    top: 12px;
    pointer-events: none;
    color: rgba(255,255,255,0.8);
    transition: 0.2s;
}

.form-group {
    position: relative;
}

.form-control:focus ~ .float-label,
.form-control:not(:placeholder-shown) ~ .float-label {
    top: -10px;
    left: 12px;
    font-size: 12px;
    color: #ffffff;
}

/* Captcha */
#captcha-container {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

#captcha-digits span {
    font-size: 22px;
    font-weight: bold;
    margin: 0 4px;
}

#btn-refresh {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
    margin-left: 10px;
}

#btn-refresh:hover {
    background: rgba(255,255,255,0.4);
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    background: #ffffff;
    color: #1a237e;
    font-weight: 600;
    border: none;
    transition: 0.3s;
}

.btn-login:hover {
    background: #e8eaf6;
    transform: translateY(-2px);
}

/* Forgot password */
.forgot-phone {
    text-align: right;
    margin-bottom: 20px;
}

.forgot-phone a {
    color: #ffffff;
    font-size: 14px;
    text-decoration: underline;
}

</style>
</head>

<body>

<form action="{{ route('loginsave') }}" method="POST" class="login-box">

    @csrf

    

    <h3>Sign In</h3>

    @if ($errors->any())
    <div class="alert alert-danger text-start">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="form-group">
        <input type="text" name="EmployeeCd" class="form-control" placeholder=" " required style="text-transform:uppercase;">
        <label class="float-label">Username</label>
    </div>

    <div class="form-group">
        <input type="password" name="passwd" class="form-control" placeholder=" " required>
        <label class="float-label">Password</label>
    </div>

    <!-- Captcha -->
    <div id="captcha-container">
        <div id="captcha-digits">
            {!! collect(str_split($captchaNumber))->map(function($digit,$i){
                $colors = ['#e74c3c','#3498db','#27ae60','#f1c40f'];
                $color = $colors[$i % 4];
                return "<span style='color:$color;'>$digit</span>";
            })->implode('') !!}
        </div>

        <button type="button" id="btn-refresh">↻</button>
    </div>

    <input type="text" name="captcha" class="form-control" placeholder="Enter captcha" required>

    @error('captcha')
        <small class="text-danger">{{ $message }}</small>
    @enderror

   

    <button type="submit" class="btn-login">Sign In</button>

</form>

<!-- Modal -->


<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* Refresh Captcha */
document.getElementById('btn-refresh').addEventListener('click', function () {

    fetch('/refresh-captcha')
        .then(response => response.json())
        .then(data => {
            document.getElementById('captcha-digits').innerHTML = data.captcha;
        })
        .catch(err => console.error('Captcha refresh error:', err));

});

/* Disable Back Button */
(function () {

    history.pushState(null, null, location.href);

    window.onpopstate = function () {
        history.go(1);
    };

})();

</script>

</body>
</html>