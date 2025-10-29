<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In</title>
  <link href="https://elitelearnersacademy.com/CSS/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-lg rounded-4">
          <div class="card-body p-4">
            <h4 class="text-center mb-4">Welcome Back</h4>
            <form action="signin.php" method="POST">
              <div class="form-floating mb-3">
                <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                <label for="email">Email address</label>
              </div>
              <div class="form-floating mb-3">
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
              </div>
              <button class="btn btn-primary w-100 rounded-3" type="submit">Sign In</button>
            </form>
            <div class="text-center mt-3">
              <small>Don't have an account? <a href="signup68a64c28-05c0-8325-89d3-794499579c5a.php">Sign Up</a></small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
