# Guía de Instalación de Docker y Docker Compose para Ubuntu

Esta guía proporciona instrucciones paso a paso para instalar Docker y Docker Compose en un sistema Ubuntu.

## Paso 1: Actualizar los Paquetes
Primero, asegúrate de que los paquetes estén actualizados.
```bash
sudo apt update
sudo apt upgrade
```

## Paso 2: Instalar Docker
1. Instala los paquetes necesarios para Docker:
   ```bash
   sudo apt install apt-transport-https ca-certificates curl software-properties-common
   ```
2. Agrega la clave GPG oficial de Docker:
   ```bash
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
   ```
3. Agrega el repositorio de Docker:
   ```bash
   echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   ```
4. Instala Docker:
   ```bash
   sudo apt update
   sudo apt install docker-ce docker-ce-cli containerd.io
   ```
5. Verifica que Docker esté instalado correctamente:
   ```bash
   sudo docker --version
   ```

## Paso 3: Configurar Docker para Ejecutar sin Sudo (Opcional)
Si deseas ejecutar Docker sin usar `sudo`:
```bash
sudo usermod -aG docker ${USER}
```
Luego, cierra sesión y vuelve a iniciar sesión o usa el siguiente comando para aplicar el cambio:
```bash
newgrp docker
```

## Paso 4: Instalar Docker Compose
1. Descarga la última versión de Docker Compose:
   ```bash
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   ```
2. Dale permisos de ejecución:
   ```bash
   sudo chmod +x /usr/local/bin/docker-compose
   ```
3. Verifica la instalación:
   ```bash
   docker-compose --version
   ```

---

# Guía para Levantar la Aplicación

Ahora que ya tienes Docker y Docker Compose instalados, te voy a explicar cómo levantar la aplicación web que hemos preparado. Sigue estos pasos:

## 1. Clonar el Repositorio desde GitHub
Primero, necesitamos clonar el proyecto alojado en GitHub. Para hacerlo, abre una terminal y ejecuta el siguiente comando:
```bash
git clone https://github.com/Jeyzet/Appweb.git
```
Esto descargará todos los archivos del proyecto en tu máquina.

## 2. Cambiar al Directorio del Proyecto
Una vez que se haya clonado el repositorio, navega al directorio del proyecto usando:
```bash
cd Appweb
```
Esto te llevará a la carpeta donde se encuentran todos los archivos necesarios para ejecutar la aplicación.

## 3. Levantar la Aplicación con Docker Compose
Ahora que estamos dentro del directorio del proyecto, usaremos Docker Compose para levantar la aplicación. Para esto, ejecuta el siguiente comando:
```bash
docker-compose up -d
```
Este comando creará y pondrá en marcha los contenedores necesarios para la aplicación (nginx, PHP, y MySQL). La opción `-d` significa que los contenedores se ejecutarán en segundo plano, por lo que la terminal no quedará ocupada.

## 4. Verificar que la Aplicación Esté Corriendo
Una vez ejecutados los comandos anteriores, la aplicación debería estar corriendo. Puedes acceder a ella a través del navegador, ingresando a:
```
http://localhost
```
Si todo está configurado correctamente, deberías ver la página principal del foro de seguridad.

## 5. Detener la Aplicación (Opcional)
Si necesitas detener la aplicación, puedes hacerlo con el siguiente comando:
```bash
docker-compose down
```
Este comando detendrá y eliminará todos los contenedores asociados al proyecto.

## Consideraciones
- Asegúrate de tener Docker y Docker Compose instalados antes de ejecutar estos comandos.
- Si encuentras problemas al levantar los contenedores, revisa los logs con el comando `docker logs <nombre_del_contenedor>` para diagnosticar posibles errores.

### Para Eliminar Máquinas:
- Detener los contenedores:
  ```bash
  docker stop $(docker ps -q)
  ```
- Eliminar los contenedores:
  ```bash
  docker rm $(docker ps -a -q)
  ```
- Eliminar imágenes (opcional):
  ```bash
  docker rmi $(docker images -q)
  ```
- Eliminar volúmenes (opcional):
  ```bash
  docker volume rm $(docker volume ls -q)
  ```
- Usar Docker Compose para bajar las máquinas:
  ```bash
  docker-compose down
  ```

---

# Crear un Usuario en la Base de Datos

## Paso 1: Conectar a la Base de Datos MySQL
Conéctate al contenedor de MySQL para verificar la base de datos:
```bash
docker exec -it mysql_db mysql -u foro_user -pforopassword foro_db
```
Esto abrirá la terminal de MySQL para la base de datos `foro_db`.

## Paso 2: Crear la Tabla `users`
Si la tabla `users` no existe, puedes crearla manualmente con la siguiente consulta SQL:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);
```
Esto creará la tabla `users` con columnas para `id`, `username`, y `password`.

## Paso 3: Insertar un Usuario de Prueba
Después de crear la tabla, inserta un usuario de prueba:
```sql
INSERT INTO users (username, password) VALUES ('admin', 'password123');
```
Esto añadirá un usuario llamado `admin` con la contraseña `password123`.

## Paso 4: Salir de MySQL
Una vez que hayas ejecutado estos comandos, puedes salir de MySQL escribiendo:
```sql
EXIT;
```

## Paso 5: Probar el Acceso
Ahora vuelve a la aplicación a través del navegador e intenta iniciar sesión con:
- **Usuario**: `admin`
- **Contraseña**: `password123`

Con estos pasos, deberías poder solucionar el problema de la tabla `users` faltante y probar la funcionalidad de inicio de sesión.

---

# Explicación del Ataque SQL Injection en el Código del Foro de Seguridad

SQL Injection (inyección SQL) es una de las vulnerabilidades de seguridad más comunes y peligrosas que puede afectar a una aplicación web. Este ataque ocurre cuando se permite que un usuario ingrese código malicioso en un campo de entrada, lo cual se ejecuta como parte de una consulta SQL. Vamos a explicar por qué este ataque ocurre en el archivo `index.php` que hemos creado, cuáles partes son vulnerables y cómo podríamos protegernos de este tipo de amenazas.

## 1. Parte Vulnerable del Código

Considera el siguiente código vulnerable:

```php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta sin preparar (vulnerable a SQL Injection)
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

En este código, el problema radica en cómo se construye la consulta SQL usando los valores ingresados por el usuario (`$username` y `$password`). Estos valores se concatenan directamente en la consulta, lo cual permite que cualquier entrada que el usuario escriba sea incluida como parte del código SQL.

Por ejemplo, si un usuario ingresa lo siguiente en el campo **username**:

```
' OR '1'='1
```

y deja el campo **password** en blanco, la consulta SQL se convierte en:

```sql
SELECT * FROM users WHERE username = '' OR '1'='1' AND password = ''
```

La condición `'1'='1'` siempre es verdadera, lo que hace que la consulta devuelva todos los usuarios en la base de datos, permitiendo el acceso sin necesidad de credenciales válidas.

## 2. ¿Por Qué Ocurre Este Ataque?

El ataque ocurre porque el código no distingue entre los datos ingresados por el usuario y el código SQL. Al concatenar directamente los valores de los campos de entrada en la consulta, se permite que el usuario controle cómo se ejecuta el código SQL. Esto significa que si el usuario escribe código SQL malicioso, este será ejecutado por la base de datos como parte de la consulta.

## 3. Cómo Protegerse Contra SQL Injection

La mejor forma de protegerse contra ataques de SQL Injection es utilizando **consultas preparadas** (también conocidas como consultas parametrizadas). Las consultas preparadas aseguran que los valores ingresados por el usuario sean tratados únicamente como datos, no como parte del código SQL.

A continuación, se muestra cómo podría modificarse el código anterior para utilizar consultas preparadas:

```php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta preparada para evitar SQL Injection
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

## 4. ¿Cómo Funcionan las Consultas Preparadas?

**Las consultas preparadas** funcionan separando la estructura de la consulta SQL de los datos que se utilizan en la misma. Primero, se define la consulta con **marcadores de posición** (`?`), y luego se envían los valores reales mediante el método `bind_param()`. Esto garantiza que los valores se traten siempre como datos y no como código.

En el ejemplo anterior:

- La consulta `"SELECT * FROM users WHERE username = ? AND password = ?"` tiene dos marcadores de posición (`?`) donde se insertan los valores del usuario y la contraseña.
- La función `$stmt->bind_param("ss", $username, $password)` se encarga de asociar esos valores de forma segura.

## 5. Otros Métodos de Prevención

- **Validación de Entrada**: Siempre que se reciban datos del usuario, asegúrate de validarlos y sanitizarlos. Aunque no es una solución completa para evitar SQL Injection, puede ayudar a reducir otros tipos de ataques.
- **Usar Módulos ORM**: Si es posible, utiliza un ORM (Object Relational Mapper) como Eloquent (para Laravel) o SQLAlchemy (para Python), que suelen manejar las consultas de una forma más segura y previenen inyecciones SQL de manera predeterminada.

## Conclusión

El ataque de inyección SQL ocurre porque los datos ingresados por el usuario se tratan como código dentro de una consulta SQL. En el código del archivo `index.php`, la vulnerabilidad fue causada por concatenar directamente los valores de `$username` y `$password` en la consulta SQL.

---

# Documentación de la Prueba de Ataque de Fuerza Bruta con Hydra

En esta sección, explicaremos cómo realizar un ataque de fuerza bruta al login de la aplicación utilizando Hydra, una herramienta popular para realizar pruebas de fuerza bruta a servicios en red. Hydra permite automatizar el proceso de probar diferentes combinaciones de usuario y contraseña para encontrar credenciales válidas.

## Paso 1: Revisar los Campos del Login
Antes de usar Hydra, necesitamos identificar cómo funciona el proceso de login de nuestra aplicación. Para esto, puedes inspeccionar el formulario con la herramienta de "Inspección" de tu navegador (usualmente se accede presionando F12).

Identifica el tipo de método HTTP (en este caso es `POST`), la URL a la que se envían los datos y los nombres de los campos del formulario (`username` y `password`). Además, necesitas identificar el mensaje de error que aparece cuando las credenciales son incorrectas (por ejemplo, "Invalid credentials").

## Paso 2: Preparar la Herramienta de Ataque
Vamos a usar el archivo `rockyou.txt` para realizar el ataque de fuerza bruta. Este archivo suele estar comprimido en Kali Linux, por lo que primero debemos descomprimirlo:

```bash
gunzip /usr/share/wordlists/rockyou.txt.gz
```

## Paso 3: Ejecutar Hydra
Después de descomprimir el archivo, podemos utilizar Hydra para intentar encontrar las credenciales del sitio web. El comando utilizado será el siguiente:

```bash
hydra -l admin -P /usr/share/wordlists/rockyou.txt 192.168.4.124 http-post-form "/:username=^USER^&password=^PASS^&login=:Invalid credentials"
```

### Explicación de los Componentes del Comando
- **-l admin**: Este parámetro especifica el nombre de usuario que vamos a probar. En este caso, estamos intentando forzar el login del usuario `admin`.
- **-P /usr/share/wordlists/rockyou.txt**: Este parámetro indica el archivo que contiene la lista de contraseñas que se probarán. En este caso, utilizamos el archivo `rockyou.txt`, que contiene miles de contraseñas comunes.
- **192.168.4.124**: Es la dirección IP del servidor donde está alojada nuestra aplicación.
- **http-post-form**: Especifica que estamos realizando un ataque a un formulario HTTP usando el método POST.
- **"/:username=^USER^&password=^PASS^&login=:Invalid credentials"**: Esta parte indica cómo se envían los datos al formulario:
  - `/` es la ruta del login.
  - `username=^USER^` indica que Hydra reemplazará `^USER^` con los valores de la lista de nombres de usuario (en este caso, solo `admin`).
  - `password=^PASS^` indica que Hydra reemplazará `^PASS^` con cada una de las contraseñas del archivo `rockyou.txt`.
  - `Invalid credentials` es el mensaje de error que aparece cuando el login falla. Hydra lo utiliza para determinar cuándo la autenticación no ha sido exitosa.

## Paso 4: Interpretar los Resultados
Una vez que ejecutas el comando, Hydra intentará múltiples combinaciones de usuario y contraseña hasta encontrar una que funcione. Si encuentra credenciales válidas, las mostrará en la terminal.

### Nota de Seguridad
Este tipo de ataque solo debe realizarse en entornos de prueba o donde tengas permiso explícito para realizar auditorías de seguridad. Ejecutar ataques de fuerza bruta sin autorización es ilegal y puede tener consecuencias graves.

## Conclusión
Hydra es una herramienta poderosa para realizar ataques de fuerza bruta a formularios de login. En este ejercicio, hemos aprendido a usarla para probar combinaciones de usuario y contraseña en nuestra aplicación de prueba. Asegúrate de utilizar estas técnicas de manera responsable y siempre en entornos donde esté permitido hacerlo.
