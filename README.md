# Projecte M3

## Índex

1. [Prerequisits necessaris abans de fer funcionar l'aplicació](#prerequisits-necessaris-abans-de-fer-funcionar-laplicació)
   - [Requisits essencials](#requisits-essencials)
   - [Xarxa](#xarxa)
   - [Verificacions ràpides](#verificacions-ràpides)
2. [Diagrama Entitat-Relació](#diagrama-entitat-relació)
3. [Instal·lació Servidor LAMP](#installació-servidor-lamp)
   - [1. Configuració inicial del sistema](#3-configuració-inicial-del-sistema)
   - [2. Instal·lació Apache](#4-installació-apache)
   - [3. Instal·lació PHP](#5-installació-php)
   - [4. Instal·lació MySQL](#6-installació-mysql)
   - [5. Configuració de permisos web](#7-configuració-de-permisos-web)
   - [6. Verificació final del sistema](#8-verificació-final-del-sistema)
4. [Script d’instal·lació i configuració servidor LAMP + BBDD amb registres](#script-dinstallació-i-configuració-servidor-lamp-amb-bbdd-amb-registres)



## Prerequisits necessaris abans de fer funcionar l'aplicació


## Requisits essencials
- **Connexió a Internet** estable. 
- **Usuari amb permisos sudo** a la màquina virtual.  
- **Ports disponibles:**  
  - `22` (SSH)  
  - `80` (HTTP)  
  - `3306` (MySQL)  

**Recursos mínims recomanats:**  
  - 2 nuclis CPU  
  - 2 GB RAM (4 GB recomanat)  
  - 20 GB d’espai en disc (40 GB recomanat)

---

## Xarxa
- Connectat correctamente a la xarxa local perquè obtingui IP.
- Assegurar que el firewall no bloqueja els ports 22, 80 i 3306.

---

## Verificacions ràpides
```bash
# Actualitzar sistema
sudo apt update && sudo apt upgrade

# Comprovar connexió de xarxa
ip a
ping -c 3 8.8.8.8

# Comprovar permisos sudo
whoami
sudo -v
``` 
## Diagrama Entitat relació

<img width="976" height="849" alt="imagen" src="https://github.com/user-attachments/assets/a01a4f57-058b-4d73-acca-de2c4605b0b3" />

## Instal·lació Servidor LAMP

### Instal·lació ISO en pendrive booteable
Instal·lem la ISO Ubuntu Server al pendrive bootable per després introduir el pendrive al servidor on volem instal·lar el sistema operatiu


### Configuració de xarxa
Configurar l'adaptador de xarxa com a "Adaptador pont" per obtenir IP automàtica via DHCP.

---

## 1. Configuració inicial del sistema

### Actualització del sistema Ubuntu Server
Un cop iniciada la VM, actualitzem el sistema:

```bash
sudo apt update
sudo apt upgrade
```

### Instal·lació i configuració SSH

Instal·lem el servidor SSH per permetre connexions remotes:

```bash
sudo apt install openssh-server
```

Comprovem que SSH s'ha iniciat correctament:

```bash
sudo systemctl status ssh
```

Verifiquem que SSH està escoltant pel port 22 (port per defecte):
```bash
sudo ss -plutn | grep :22
```

Assegurem que SSH s'iniciï automàticament:
```bash
sudo systemctl enable ssh
```
 
Obtenir la IP que permetrà connectar-nos remotament des del nostre ordinador:
```bash
ip a
```

Des de la màquina host (la vostra màquina física), connecteu-vos via SSH:

```bash
# Substituir IP_DEL_EQUIP per la IP obtinguda anteriorment
# Substituir USUARI per el nom del teu usuari de la VM
ssh USUARI@IP_DE_LA_VM
```

Verificar la connexió SSH a la màquina servidor:
```bash
# Veure informació del sistema
uname -a
```

## 2. Instal·lació Apache

### Instal·lació del servidor web Apache
Instal·lem Apache amb la configuració per defecte:

```bash
sudo apt install apache2
```

### Verificació del servei Apache
Comprovem que Apache s'ha iniciat correctament:

```bash
sudo systemctl status apache2
```

### Verificació dels ports d'Apache
Verifiquem que Apache està escoltant al port 80:

```bash
sudo ss -plutn | grep :80
```

### Habilitació d'Apache a l'arrencada
Assegurem que Apache s'iniciï automàticament:

```bash
sudo systemctl enable apache2
```

### Test de la pàgina per defecte
Obtenim la IP de la màquina i comprovem la pàgina per defecte:

```bash
ip addr show
# Navegar a http://IP_DE_LA_VM per veure la pàgina per defecte d'Apache
```

---

## 3. Instal·lació PHP

### Instal·lació de PHP i mòduls necessaris
Instal·lem PHP amb els mòduls necessaris per al projecte web:

```bash
sudo apt install php libapache2-mod-php php-mysql php-mysqli php-json php-curl php-mbstring php-gd
```

### Verificació de la instal·lació PHP
Comprovem la versió de PHP instal·lada i la ruta del fitxer de configuració i d'inicialització de PHP:

```bash
php -v

php --ini
```

### Configuració PHP per fer-la més vulnerable
Editem el fitxer de configuració per fer-lo menys segur (per l'auditoria posterior):

```bash
sudo nano /etc/php/8.X/apache2/php.ini
```

Modifiquem aquests paràmetres per fer la configuració per un **entorn de desenvolupament** (només de proves):

```ini
display_errors = On
display_startup_errors = On
expose_php = On
log_errors = Off
allow_url_fopen = On
```

### Creació d'un fitxer phpinfo per verificació
Creem un fitxer per comprovar la configuració de PHP:

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

### Reiniciar Apache per aplicar canvis
Reiniciem Apache per aplicar la configuració de PHP:

```bash
sudo systemctl restart apache2
```

### Verificació del servei Apache després dels canvis
```bash
sudo systemctl status apache2
```

### Test de PHP
Naveguem a `http://IP_DEL_EQUIP/info.php` per veure la informació de PHP.

---

## 4. Instal·lació MySQL

### Instal·lació del servidor MySQL
Instal·lem MySQL Server amb configuració per defecte:

```bash
sudo apt install mysql-server
```

### Verificació del servei MySQL
Comprovem que MySQL s'ha iniciat correctament:

```bash
sudo systemctl status mysql
```

### Verificació dels ports de MySQL
Verifiquem que MySQL està escoltant al port 3306:

```bash
sudo ss -plutn | grep :3306
```

### Habilitació de MySQL a l'arrencada
Assegurem que MySQL s'iniciï automàticament:

```bash
sudo systemctl enable mysql
```

### Accés a MySQL
Accedim a MySQL com a root sense password:

```bash
sudo mysql -u root -p
```

Dins de MySQL, executem:

```sql
SHOW DATABASES;

CREATE DATABASE plataformaweb;

-- Crear un usuari per a l'aplicació web amb una password insegura i accés desde qualsevol IP (també podeu fer servir root)
CREATE USER 'usuariweb'@'%' IDENTIFIED BY 'password123';

-- Donar tots els privilegis a l'usuari web sobre totes les bases de dades i taules (configuració insegura només per desenvolupament)
GRANT ALL PRIVILEGES ON *.* TO 'usuariweb'@'%';

FLUSH PRIVILEGES;

EXIT;
```

### Configuració per accés remot a MySQL
Editem el fitxer de configuració de MySQL/MariaDB per permetre connexions remotes:

```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

Busquem la línia bind-address i la comentem o canviem per acceptar connexions de qualsevol IP:

```bash
# bind-address = 127.0.0.1
bind-address = 0.0.0.0
```

Reiniciem el servei de per aplicar els canvis:

```bash
sudo systemctl restart mysql
```

Verifiquem que el servei de MySQL escolta per totes les interfícies (loopback i enp0s3)

```bash
sudo ss -plutn | grep :3306
```

### Test de connexió amb l'usuari creat
Provem la connexió amb el nou usuari en local i en remot:

```bash
# Localment en el servidor
mysql -u usuariweb -p
# Introduir password: password123

# Remotament des del teu ordinador
mysql -u usuariweb -p -h IP_DE_LA_VM
# Introduir password: password123
```

Un cop dins de MySQL:

```sql
SHOW DATABASES;

-- Llistar tots els usuaris i des d'on es poden connectar
SELECT User, Host FROM mysql.user;

-- Veure els privilegis de l'usuariweb
SHOW GRANTS FOR 'usuariweb'@'%';

EXIT;
```

## 5. Configuració de permisos web

### No modifiquem la configuració del propietari de la carpeta arrel de la pàgina web
L'usuari per defecte de la carpeta web és ROOT.

```bash
ls -la /var/www/html
```

### No modifiquem la configuració de permisos de la carpeta web
Com el propietari és ROOT, l'usuari Apache (www-data) no podrà escriure a la carpeta.
No podrà pujar fitxer (uploads), enregistrar logs, guardar fitxer dinàmicament, caché o sessions PHP, etc.
Durant el desenvolupament inicial haureu de plantejar com podeu solucionar aquest problema.

```bash
#sudo chmod -R 755 /var/www/html
```

## 6. Verificació final del sistema

### Test de tots els serveis
Comprovem que tots els serveis estan actius:

```bash
# SSH
sudo systemctl status ssh

# Apache
sudo systemctl status apache2

# MySQL
sudo systemctl status mysql

# Verificació de ports
sudo ss -plutn | grep -E ':22|:80|:3306'
```

### Test de la pàgina web per defecte d'Apache
Naveguem a `http://IP_DEL_EQUIP/` per veure la pàgina per defecte d'Apache.

### Test de PHP
Naveguem a `http://IP_DEL_EQUIP/info.php` per verificar que PHP funciona correctament.

## Script d'instal·lació i configuració servidor LAMP amb BBDD amb registres

Per crear l'script realitzem lo següent:

Creem un arxiu executant la comanda touch anomenat script.sh:
```bash
touch script.sh
```
Un cop creat l'script, l'editem utilitzant la comanda nano:

```bash
nano script.sh
```
Afegim dins de l'arxiu script.sh el següent contingut:

```bash
#!/bin/bash

echo "Actualitzant el sistema..."
sudo apt update && sudo apt upgrade -y

echo "Instal·lant el servidor SSH..."
sudo apt install -y openssh-server
sudo systemctl enable ssh
sudo systemctl start ssh

echo "Instal·lant Apache..."
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2

echo "Instal·lant PHP i els mòduls necessaris..."
sudo apt install -y php libapache2-mod-php php-mysql php-mysqli php-json php-curl php-mbstring php-gd

echo "Configurant PHP per crear un entorn de desenvolupament vulnerable..."
PHP_INI=$(php --ini | grep "Loaded Configuration" | awk '{print $4}')
sudo sed -i 's/display_errors = .*/display_errors = On/' $PHP_INI
sudo sed -i 's/display_startup_errors = .*/display_startup_errors = On/' $PHP_INI
sudo sed -i 's/expose_php = .*/expose_php = On/' $PHP_INI
sudo sed -i 's/log_errors = .*/log_errors = Off/' $PHP_INI
sudo sed -i 's/allow_url_fopen = .*/allow_url_fopen = On/' $PHP_INI

echo "Creant l'arxiu info.php..."
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php

echo "Reiniciant Apache per aplicar tots els canvis realitzats..."
sudo systemctl restart apache2

echo "Instal·lant MySQL Server..."
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

echo "Configurant MySQL per permetre accés remot..."
MYSQL_CONF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if grep -q "^bind-address" "$MYSQL_CONF"; then
  sudo sed -i 's/^bind-address\s*=.*/bind-address = 0.0.0.0/' "$MYSQL_CONF"
else
  echo "bind-address = 0.0.0.0" | sudo tee -a "$MYSQL_CONF"
fi
sudo systemctl restart mysql

echo "Configurant base de dades, taules i registres..."
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS plataforma_videojocs;
USE plataforma_videojocs;

CREATE USER IF NOT EXISTS 'usuariweb'@'%' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON plataforma_videojocs.* TO 'usuariweb'@'%';
FLUSH PRIVILEGES;

sudo mysql -u root <<'EOF'
CREATE DATABASE IF NOT EXISTS plataforma_videojocs
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plataforma_videojocs;

CREATE TABLE IF NOT EXISTS usuaris (
  id INT NOT NULL AUTO_INCREMENT,
  nickname VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  nom VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  password_hash VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  cognom VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  data_naixement DATE DEFAULT NULL,
  data_registre DATETIME DEFAULT CURRENT_TIMESTAMP,
  photo VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT '../frontend/imatges/users/default_user.png',
  PRIMARY KEY (id),
  UNIQUE KEY (email),
  UNIQUE KEY (nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuaris (id, nickname, nom, email, password_hash, cognom, data_naixement, data_registre, photo) VALUES
(1,'Tommy1701','Tomas','tomas@elias.cat','Nolose123.','Elias','2025-10-03','2025-10-21 15:06:50','../frontend/imatges/users/user_1_1762358068.jpg'),
(2,'Gabriele','Gabriele','gabriele@elias.cat','Nolose123.','Elias','2025-10-03','2025-10-21 15:07:29','../frontend/imatges/users/user_2_1761764281.png');

CREATE TABLE IF NOT EXISTS jocs (
  id INT NOT NULL AUTO_INCREMENT,
  nom_joc VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcio TEXT COLLATE utf8mb4_unicode_ci,
  categoria VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Joc',
  temps_aprox_min INT DEFAULT 0,
  num_jugadors VARCHAR(25) COLLATE utf8mb4_unicode_ci DEFAULT '1 jugador',
  valoracio DECIMAL(3,1) DEFAULT 0.0,
  actiu TINYINT(1) DEFAULT 1,
  tipus ENUM('Free','Premium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Premium',
  cover_image_url VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  screenshots_json JSON DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO jocs (id, nom_joc, descripcio, categoria, temps_aprox_min, num_jugadors, valoracio, actiu, tipus, cover_image_url)
VALUES (1,'Space Battle','Joc de combat espacial','Acció',45,'1 jugador',4.5,1,'Free','/frontend/imatges/jocs/spacebattle.png');

CREATE TABLE IF NOT EXISTS nivells_joc (
  id INT NOT NULL AUTO_INCREMENT,
  joc_id INT NOT NULL,
  nivell INT NOT NULL,
  configuracio_json JSON NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (joc_id) REFERENCES jocs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO nivells_joc (joc_id, nivell, configuracio_json)
VALUES (1,1,'{"enemics":5,"boss":false}'),
       (1,2,'{"enemics":10,"boss":true}');

CREATE TABLE IF NOT EXISTS partides (
  id INT NOT NULL AUTO_INCREMENT,
  usuari_id INT NOT NULL,
  joc_id INT NOT NULL,
  nivell_jugat INT NOT NULL,
  puntuacio_obtinguda INT NOT NULL,
  data_partida DATETIME DEFAULT CURRENT_TIMESTAMP,
  durada_segons INT DEFAULT 0,
  dades_partida_json JSON,
  PRIMARY KEY (id),
  FOREIGN KEY (usuari_id) REFERENCES usuaris(id),
  FOREIGN KEY (joc_id) REFERENCES jocs(id)
);

INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, durada_segons, dades_partida_json)
VALUES (1,1,1,300,120,'{"moviments":50}');

CREATE TABLE IF NOT EXISTS progres_usuari (
  id INT NOT NULL AUTO_INCREMENT,
  usuari_id INT NOT NULL,
  joc_id INT NOT NULL,
  nivell_actual INT DEFAULT 1,
  puntuacio_total INT DEFAULT 0,
  data_ultima_partida DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (usuari_id) REFERENCES usuaris(id),
  FOREIGN KEY (joc_id) REFERENCES jocs(id)
);

INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_total)
VALUES (1,1,2,300);

CREATE TABLE IF NOT EXISTS wishlist (
  id INT NOT NULL AUTO_INCREMENT,
  usuari_id INT NOT NULL,
  joc_id INT NOT NULL,
  data_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (usuari_id) REFERENCES usuaris(id),
  FOREIGN KEY (joc_id) REFERENCES jocs(id)
);

INSERT INTO wishlist (usuari_id, joc_id)
VALUES (1,1);

EOF

echo "Verificant serveis..."
sudo systemctl status ssh | grep Active
sudo systemctl status apache2 | grep Active
sudo systemctl status mysql | grep Active
sudo ss -plutn | grep -E ':22|:80|:3306'

echo "Instal·lació completada. Accedeix a:"
echo " - Apache: http://IP_DEL_EQUIP"
echo " - PHP info: http://IP_DEL_EQUIP/info.php"
echo " - MySQL: mysql -u usuariweb -p -h IP_DEL_EQUIP"
echo " - Contrasenya de usuariweb a MySQL: password123"

```
Un cop afegit el contingut al fitxer script.sh, proporcionem permisos d'execució a l'arxiu perquè es pugui executar l'script executant la comanda chmod:

```bash
sudo chmod +x script.sh
```
Finalment, quan s'hagin proporcionat permisos d'execució a l'arxiu, l'executem de la següent manera:
```bash
sudo ./script.sh
```

