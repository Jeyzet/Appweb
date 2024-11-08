<?php
session_start(); // Iniciar la sesión al comienzo del archivo
?>
<!DOCTYPE html>
<html>
<head>
    <title>Foro de Seguridad</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        form {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .news-item {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        header, footer {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: center;
        }
        img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Foro de Seguridad</h1>
    </header>
    <div class="container">
        <img src="https://blogger.googleusercontent.com/img/a/AVvXsEjToV-2qrOOF1BkQg0-7zLFfsA1QVJdK1gaMB0Wc2-4I_ucmlOwtqmqZEbVTvNTUoMixdDZEqxrhoaBbjkcOSXLxfjnVIhJZK7LmZn_1fXTGUNntP6lChFLTx3tc80x68jmlR7wpppcyck8RZMQtc5J8X2US0QcATax4Yb9jDOqVXfXh9QN2o-9ofrjyPct=s1168" alt="Cybersecurity Banner">

        <h2>Login</h2>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <h2>Buscar Noticias</h2>
        <form method="post">
            <input type="text" name="searchTerm" placeholder="Buscar noticias...">
            <button type="submit" name="search">Buscar</button>
        </form>

        <h2>Noticias de Seguridad</h2>
        <?php
        $mysqli = new mysqli("db", "foro_user", "foropassword", "foro_db");

        // Verificar la conexión
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        // Manejar el formulario de login
        if (isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ? AND password = ?");

            if ($stmt) {
                $stmt->bind_param("ss", $username, $password);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $_SESSION['user'] = $username;
                    echo "<div class='alert alert-success'>Login successful!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Invalid credentials.</div>";
                }

                $stmt->close();
            } else {
                echo "<div class='alert alert-danger'>Error preparing query: " . $mysqli->error . "</div>";
            }
        }

        // Manejar el formulario de búsqueda de noticias
        if (isset($_POST['search'])) {
            $searchTerm = $_POST['searchTerm'];
            $stmt = $mysqli->prepare("SELECT * FROM news WHERE title LIKE ?");

            if ($stmt) {
                $likeTerm = "%$searchTerm%";
                $stmt->bind_param("s", $likeTerm);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='news-item'>";
                        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                        echo "<p>" . htmlspecialchars($row['content']) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-warning'>No news found.</div>";
                }

                $stmt->close();
            } else {
                echo "<div class='alert alert-danger'>Error preparing query: " . $mysqli->error . "</div>";
            }
        }

        // Mostrar todas las noticias
        $result = $mysqli->query("SELECT * FROM news");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='news-item'>";
                echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p>" . htmlspecialchars($row['content']) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Error in query: " . $mysqli->error . "</div>";
        }
        ?>
    </div>
    <footer>
        <p>&copy; 2024 Foro de Seguridad - Todos los derechos reservados.</p>
    </footer>
</body>
</html>
