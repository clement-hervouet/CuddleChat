
<?php
// Include config file
require_once 'config/config.php';


// Define variables and initialize with empty values
$username = $password = $confirm_password = $lastname = $firstname = "";

$username_err = $password_err = $confirm_password_err = $lastname_err = $firstname_err = "";

// Process submitted form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Check if username is empty
	$raw_username = isset($_POST['username']) ? $_POST['username'] : null;
	if (empty(trim($raw_username))) {
		$username_err = "Please enter a username.";
	} else {
		$param_username = sanitize_input($raw_username);

		// Basic username format validation (alphanumeric + underscore, 3-50 chars)
		if (!is_safe_input($param_username) || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $param_username)) {
			$username_err = 'Invalid username format.';
		} else {
			// Use transaction to avoid race conditions when checking/inserting
			try {
				$pdo->beginTransaction();

				// Lock the row if exists to prevent race conditions
				$sql = 'SELECT id_user FROM users WHERE username = ? FOR UPDATE';
				$stmt = $pdo->prepare($sql);
				$stmt->execute([$param_username]);

				if ($stmt->rowCount() == 1) {
					$username_err = 'This username is already taken.';
					// Leave transaction open for rollback later when needed
				} else {
					$username = $param_username;
				}
			} catch (Exception $e) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}
				$username_err = 'Something went wrong. Please try again later.';
			}
		}
	}

	// Validate firstname
	$raw_firstname = isset($_POST['firstname']) ? $_POST['firstname'] : null;
	if (empty(trim($raw_firstname))) {
		$firstname_err = "Please enter a first name.";
	} else {
		$param_firstname = sanitize_input($raw_firstname);
		if (!is_safe_input($param_firstname) || !preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{1,50}$/u", $param_firstname)) {
			$firstname_err = "Invalid first name format.";
		} else {
			$firstname = $param_firstname;
		}
	}

	// Validate lastname
	$raw_lastname = isset($_POST['lastname']) ? $_POST['lastname'] : null;
	if (empty(trim($raw_lastname))) {
		$lastname_err = "Please enter a last name.";
	} else {
		$param_lastname = sanitize_input($raw_lastname);
		if (!is_safe_input($param_lastname) || !preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{1,50}$/u", $param_lastname)) {
			$lastname_err = "Invalid last name format.";
		} else {
			$lastname = $param_lastname;
		}
	}

	// Validate password
	if (empty(trim($_POST["password"]))) {
		$password_err = "Please enter a password.";
	} elseif (strlen(trim($_POST["password"])) < 6) {
		$password_err = "Password must have atleast 6 characters.";
	} else {
		$password = trim($_POST["password"]);
	}

	// Validate confirm password
	if (empty(trim($_POST["confirm_password"]))) {
		$confirm_password_err = "Please confirm password.";
	} else {
		$confirm_password = trim($_POST["confirm_password"]);
		if (empty($password_err) && ($password != $confirm_password)) {
			$confirm_password_err = "Password did not match.";
		}
	}

	// Check input error before inserting into database
	if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($lastname_err) && empty($firstname_err)) {
		try {
			// Ensure transaction started earlier during username check; if not, start one
			if (!$pdo->inTransaction()) {
				$pdo->beginTransaction();
			}

			// Prepare insert statement
			$sql = 'INSERT INTO users (username, password, lastname, firstname) VALUES (?,?,?,?)';
			$stmt = $pdo->prepare($sql);

			// Set parameters
			$param_password = password_hash($password, PASSWORD_DEFAULT);

			// Attempt to execute
			if ($stmt->execute([$username, $param_password, $lastname, $firstname])) {
				$pdo->commit();
				header('location: connections/login.php');
				exit;
			} else {
				// Execution failed
				$pdo->rollBack();
				echo "Something went wrong. Try signing in again.";
			}
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
				
			}
			
			echo "Something went wrong. Try signing in again.";
		}
	} else {
		// Validation failed — rollback any open transaction from username check
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Register</title>
	<link href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/cosmo/bootstrap.min.css" rel="stylesheet" integrity="sha384-qdQEsAI45WFCO5QwXBelBe1rR9Nwiss4rGEqiszC+9olH1ScrLrMQr1KmDR964uZ" crossorigin="anonymous">
	<link rel="stylesheet" href="../assets/static/css/style.css">

</head>

<body>
	<main>
		<section class="container wrapper">
			<h2 class="display-4 pt-3">Register</h2>
			<p class="text-center">Fill this form to register.</p>
			<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
				<div class="form-group <?php (!empty($username_err)) ? 'has_error' : ''; ?>">
					<label for="username">Username</label>
					<input type="text" name="username" id="username" class="form-control" value="<?php echo $username ?>">
					<span class="help-block">This username <b>can't</b> be changed later</span>
					<br>
					<span class="help-block"><?php echo $username_err; ?></span>
				</div>

				<div class="form-group <?php (!empty($firstname_err)) ? 'has_error' : ''; ?>">
					<label for="firstname">Firstname</label>
					<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo htmlspecialchars($firstname); ?>">
					<span class="help-block"><?php echo $firstname_err; ?></span>
				</div>

				<div class="form-group <?php (!empty($lastname_err)) ? 'has_error' : ''; ?>">
					<label for="lastname">Lastname</label>
					<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo htmlspecialchars($lastname); ?>">
					<span class="help-block"><?php echo $lastname_err; ?></span>
				</div>

				<div class="form-group <?php (!empty($password_err)) ? 'has_error' : ''; ?>">
					<label for="password">Password</label>
					<input type="password" name="password" id="password" class="form-control" value="<?php echo $password ?>">
					<span class="help-block"><?php echo $password_err; ?></span>
				</div>

				<div class="form-group <?php (!empty($confirm_password_err)) ? 'has_error' : ''; ?>">
					<label for="confirm_password">Confirm password</label>
					<input type="password" name="confirm_password" id="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
					<span class="help-block"><?php echo $confirm_password_err; ?></span>
				</div>

				<div class="form-group">
					<input type="submit" class="btn btn-block btn-outline-success" value="Register">
				</div>
				<p>Already have an account? <a href="connections/login.php">Login here</a>.</p>
			</form>
		</section>
	</main>
</body>

</html>
