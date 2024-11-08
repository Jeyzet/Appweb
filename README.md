# Docker and Docker Compose Installation Guide for Ubuntu

This guide provides step-by-step instructions for installing Docker and Docker Compose on an Ubuntu system.

## Step 1: Update Packages
First, ensure that your packages are updated.
```bash
sudo apt update
sudo apt upgrade
```

## Step 2: Install Docker
1. Install the required packages for Docker:
   ```bash
   sudo apt install apt-transport-https ca-certificates curl software-properties-common
   ```
2. Add Docker's official GPG key:
   ```bash
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
   ```
3. Add the Docker repository:
   ```bash
   echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   ```
4. Install Docker:
   ```bash
   sudo apt update
   sudo apt install docker-ce docker-ce-cli containerd.io
   ```
5. Verify that Docker is installed correctly:
   ```bash
   sudo docker --version
   ```

## Step 3: Configure Docker to Run Without Sudo (Optional)
If you want to run Docker without using `sudo`:
```bash
sudo usermod -aG docker ${USER}
```
Then, log out and log back in or use the following command to apply the change:
```bash
newgrp docker
```

## Step 4: Install Docker Compose
1. Download the latest version of Docker Compose:
   ```bash
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   ```
2. Give execution permissions:
   ```bash
   sudo chmod +x /usr/local/bin/docker-compose
   ```
3. Verify the installation:
   ```bash
   docker-compose --version
   ```

---

# Guide to Deploy the Application

Now that you have Docker and Docker Compose installed, let's go over how to deploy the web application we've prepared. Follow these steps:

## 1. Clone the Repository from GitHub
First, we need to clone the project hosted on GitHub. To do this, open a terminal and run the following command:
```bash
git clone https://github.com/Jeyzet/Appweb.git
```
This will download all the project files to your machine.

## 2. Change to the Project Directory
Once the repository has been cloned, navigate to the project directory using:
```bash
cd Appweb
```
This will take you to the folder where all the files needed to run the application are located.

## 3. Start the Application with Docker Compose
Now that we are inside the project directory, we will use Docker Compose to start the application. To do this, run the following command:
```bash
docker-compose up -d
```
This command will create and launch the containers needed for the application (nginx, PHP, and MySQL). The `-d` option means that the containers will run in the background, so the terminal will not be occupied.

## 4. Verify the Application is Running
Once the above commands have been executed, the application should be running. You can access it through your browser by navigating to:
```
http://localhost
```
If everything is configured correctly, you should see the main page of the security forum.

## 5. Stop the Application (Optional)
If you need to stop the application, you can do so with the following command:
```bash
docker-compose down
```
This command will stop and remove all containers associated with the project.

## Considerations
- Make sure Docker and Docker Compose are installed before executing these commands.
- If you encounter issues when starting the containers, check the logs with the command `docker logs <container_name>` to diagnose possible errors.

### To Remove Machines:
- Stop the containers:
  ```bash
  docker stop $(docker ps -q)
  ```
- Remove the containers:
  ```bash
  docker rm $(docker ps -a -q)
  ```
- Remove images (optional):
  ```bash
  docker rmi $(docker images -q)
  ```
- Remove volumes (optional):
  ```bash
  docker volume rm $(docker volume ls -q)
  ```
- Use Docker Compose to bring down the machines:
  ```bash
  docker-compose down
  ```

---

# Create a User in the Database

## Step 1: Connect to the MySQL Database
Connect to the MySQL container to verify the database:
```bash
docker exec -it mysql_db mysql -u foro_user -pforopassword foro_db
```
This will open the MySQL terminal for the `foro_db` database.

## Step 2: Create the `users` Table
If the `users` table does not exist, you can create it manually with the following SQL query:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);
```
This will create the `users` table with columns for `id`, `username`, and `password`.

## Step 3: Insert a Test User
After creating the table, insert a test user:
```sql
INSERT INTO users (username, password) VALUES ('admin', 'password123');
```
This will add a user named `admin` with the password `password123`.

## Step 4: Exit MySQL
Once you have executed these commands, you can exit MySQL by typing:
```sql
EXIT;
```

## Step 5: Test Access
Now go back to the application through the browser and try logging in with:
- **Username**: `admin`
- **Password**: `password123`

With these steps, you should be able to solve the missing `users` table problem and test the login functionality.

---

# SQL Injection Attack Explanation for the Security Forum Code

SQL Injection (SQLi) is one of the most common and dangerous security vulnerabilities that can affect a web application. This attack occurs when a user is allowed to enter malicious code in an input field, which is then executed as part of an SQL query. Let's explain why this attack occurs in the `index.php` file that we have created, which parts are vulnerable, and how we could protect ourselves from these kinds of threats.

## 1. Vulnerable Part of the Code

Consider the following vulnerable code:

```php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Unprepared query (vulnerable to SQL Injection)
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $mysqli->query($query);

    if ($result && $result->num_rows > 0) {
        $_SESSION['user'] = $username;
        echo "<div class='alert alert-success'>Login successful!</div>";
    } else {
        echo "<div class='alert alert-danger'>Invalid credentials.</div>";
    }
}
```

In this code, the problem lies in how the SQL query is constructed using user-provided values (`$username` and `$password`). These values are directly concatenated into the query, which means that any input the user types will be included as part of the SQL code.

For example, if a user enters the following in the **username** field:

```
' OR '1'='1
```

and leaves the **password** field empty, the SQL query becomes:

```sql
SELECT * FROM users WHERE username = '' OR '1'='1' AND password = ''
```

The condition `'1'='1'` is always true, which makes the query return all users in the database, allowing access without valid credentials.

## 2. Why Does This Attack Occur?

The attack occurs because the code does not distinguish between user-provided data and SQL code. By directly concatenating the input values into the query, the user is allowed to control how the SQL code is executed. This means that if the user writes malicious SQL code, it will be executed by the database as part of the query.

## 3. How to Protect Against SQL Injection

The best way to protect against SQL Injection attacks is to use **prepared statements** (also known as parameterized queries). Prepared statements ensure that user-provided values are treated only as data, not as part of the SQL code.

Below is an example of how the code could be modified to use prepared statements:

```php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepared statement to prevent SQL Injection
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
```

## 4. How Do Prepared Statements Work?

**Prepared statements** work by separating the structure of the SQL query from the data being used in it. First, the query is defined with **placeholders** (`?`), and then the actual values are sent using the `bind_param()` method. This ensures that the values are always treated as data and cannot modify the structure of the query.

In the example above:

- The query `"SELECT * FROM users WHERE username = ? AND password = ?"` has two placeholders (`?`) where the user's username and password are inserted.
- The function `$stmt->bind_param("ss", $username, $password)` securely binds those values to the placeholders.

## 5. Other Prevention Methods

- **Input Validation**: Always validate and sanitize user input. While this is not a complete solution to prevent SQL

