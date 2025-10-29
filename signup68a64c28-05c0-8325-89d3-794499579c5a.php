<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up</title>
  <link href="https://elitelearnersacademy.com/CSS/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height:100vh;">

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg rounded-4">
          <div class="card-body p-4">
            <h3 class="text-center mb-4">Create Account</h3>
            <form action="signup.php" method="POST">
              <div class="mb-3">
                <label for="username" class="form-label">Full Name</label>
                <input type="text" name="username" class="form-control" id="username" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" id="email" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required>
              </div>
              <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <input type="password" name="confirmPassword" class="form-control" id="confirmPassword" required>
              </div>
              <button type="submit" class="btn btn-success w-100">Sign Up</button>
            </form>
            <p class="text-center mt-3 mb-0">
              Already have an account? <a href="index.php">Sign In</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
