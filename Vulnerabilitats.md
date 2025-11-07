# AUDITORIA DE SEGURETAT - PLATAFORMA DE VIDEOJOCS

**Objectiu:** Identificar vulnerabilitats de seguretat intencionades i involuntàries per a la Fase 3 del projecte (Correcció).
**Estat:** Vulnerable (Entorn de Desenvolupament)

## 1. VULNERABILITAT CRÍTICA: INJECCIÓ (A03:2021 - Injection)

La fallada més greu es troba en l'ús de la concatenació directa d'entrades d'usuari a les consultes SQL, permetent l'atac d'**SQL Injection (SQLi)**.

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **DADES VULNERABLES** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **URL (GET `joc_id`)** | `backend/juego.php` | `$joc_id` | La consulta `SELECT nom_joc FROM jocs WHERE id = $joc_id` concatena la variable sense sanejar. Un atacant pot manipular la URL amb `?joc_id=1 OR 1=1 --` per saltar la lògica o consultar dades no autoritzades. |
| **Legacy CRUD (GET `id`)** | `backend/funcions/consultar_usuari.php` | `$_GET['id']` | La consulta `SELECT * FROM usuaris WHERE id = $id` és directament vulnerable a SQLi clàssic. |
| **Legacy CRUD (POST `id`)** | `backend/funcions/eliminar_usuari.php` | `$_POST['id']` | El *script* executa un `DELETE FROM usuaris WHERE id = $id`, permetent eliminar qualsevol usuari si es proporciona una entrada maliciosa. |
| **Legacy CRUD (POST `id` + `nom_usuari`)** | `backend/funcions/actualitzar_usuari.php` | `$_POST['id']`, `$_POST['nom_usuari']` | La sentència `UPDATE` no utilitza *prepared statements* ni per la clau (ID) ni per el valor (nom d'usuari), exposant dues vies d'injecció. |

**Mitigació Clau:** Implementar `mysqli::prepare()` i `bind_param()` a **totes** les consultes SQL.

## 2. ERRORS D'AUTENTICACIÓ I DISSENY (A07:2021 / A01:2021)

### 2.1. Fallada de Criptografia (Contrasenyes en Text Pla)

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Registre** | `backend/funcions/crear_usuari.php` | Contrasenya en Text Pla (No Hashing) | La variable `$password` s'insereix directament a la base de dades. Si la BBDD es veu compromesa (p. ex. descarregant el fitxer `.sql` exposed), totes les contrasenyes dels usuaris queden exposades. |
| **Login** | `backend/funcions/validacio_login.php` | Verificació en Text Pla | El codi compara la contrasenya introduïda amb la de la base de dades utilitzant `$usuari['password_hash'] === $pass`. Això és incorrecte; s'hauria d'utilitzar la funció `password_verify()` amb una contrasenya *hashed*. |

### 2.2. Insecure Direct Object Reference (IDOR) i Broken Access Control

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Legacy CRUD** | `backend/funcions/consultar_usuari.php`, `actualitzar_usuari.php`, `eliminar_usuari.php` | IDOR i Broken Access Control | Aquests fitxers confien plenament en l'ID enviat per l'usuari (GET/POST) i no comproven si l'usuari autenticat té permisos (o fins i tot si està autenticat). Un atacant podria interactuar amb els comptes d'altres usuaris modificant l'ID a la URL o al formulari. |
| **API Wishlist, Rating, Redeem** | `backend/api/toggle_wishlist.php`, `set_rating.php`, `canjear_juego.php` | IDOR (Parcial) | Encara que el codi comprova `$usuari_id = $_SESSION['user_id']`, el **JSON d'entrada no es valida per a IDOR** (p. ex., si l'usuari 1 intenta canviar el *joc_id* de l'usuari 2, l'API ho bloqueja, però és una bona pràctica assegurar-se que els IDs no puguin ser influenciats pel client). |

## 3. INJECCIÓ AL CLIENT (A03:2021 - XSS) i CSRF

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Legacy CRUD Output** | `backend/funcions/consultar_usuari.php` | Reflected / Stored XSS | No s'utilitza `htmlspecialchars()` per sanejar la sortida de la BBDD. Si un camp (`nickname` o `email`) conté un *script* maliciós, s'executarà al navegador. |
| **Tots els Formularis / APIs** | `backend/perfil.php`, `backend/funcions/validacio_login.php`, `backend/api/*.php` (POST) | CSRF (Cross-Site Request Forgery) | **Cap formulari ni API que accepti dades POST utilitza *tokens* CSRF**. Un atacant pot crear un formulari fals en un lloc extern i enviar la petició al teu domini aprofitant la sessió activa de l'usuari. |

## 4. VULNERABILITATS DE CONFIGURACIÓ / SISTEMA

Aquestes vulnerabilitats es deriven de la configuració de desenvolupament no segura i de la gestió incorrecta d'arxius:

| **VULNERABILITAT** | **FITXER/CONFIG** | **IMPACTE** |
| --- | --- | --- |
| **Exposició de Dades Sensibles** | `m3-projecte-1/plataforma_videojocs.sql` (Arrel) | **CRÍTICA:** El fitxer de volcat de la BBDD és accessible per URL i exposa l'esquema i les **contrasenyes en text pla**. |
| **Permisos de Directori Insegurs** | Directori d'uploads (p. ex. `frontend/imatges/users/`) amb `chmod 777` | L'ús de permisos 777 trenca el Principi del Mínim Privilegi, permetent l'escriptura a qualsevol usuari, cosa que és un risc per a la integritat del sistema. |
| **Command Injection Potential** | `backend/backend.dp.php` | El *script* de *backup* utilitza `shell_exec(mysqldump ...)`. Tot i que no hi ha entrades d'usuari directes, l'ús de funcions d'execució de comandes és un **patró de codi d'alt risc** que hauria de ser evitat o molt restringit. |
| **Configuració PHP Insegura** | `php.ini` (`display_errors = On`, `expose_php = On`, `allow_url_fopen = On`) | Aquesta configuració revela informació de l'entorn (`expose_php`), detalls del servidor (`display_errors`) i podria habilitar atacs de RFI (Remote File Inclusion) amb `allow_url_fopen`. |

## 5. VULNERABILITATS DE FILE UPLOAD

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Pujar Foto** | `backend/perfil.php` | Validació Incompleta de Fitxers / RCE | El codi **no valida el Magic Number** ni es garanteix que el nom de l'arxiu guardat utilitzi una extensió fixa i no executable (`.jpg` forçat). Això permet a un atacant pujar un arxiu maliciós (`shell.php`) fent-se passar per una imatge i executar-lo al servidor (Remote Code Execution - RCE). |

## 6. ERRORS DE GESTIÓ DE SESSIONS I RATE LIMITING (A07:2021)

Aquests punts afecten directament la robustesa de l'autenticació i la disponibilitat del servei.

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Login** | `backend/funcions/validacio_login.php` | **Session Fixation** | Després d'un login exitós, no es crida a `session_regenerate_id(true)`. Un atacant pot fixar l'ID de la sessió de la víctima abans del login i, un cop la víctima inicia sessió, l'atacant pot utilitzar aquest ID per suplantar la identitat. |
| **Login / Registre** | `backend/funcions/validacio_login.php`, `crear_usuari.php` | **Brute Force / DoS (Sense Rate Limiting)** | No hi ha cap mecanisme de límit de peticions o bloqueig temporal per IP. Un atacant pot provar un nombre il·limitat de combinacions de contrasenya (Brute Force) o enviar peticions massives per saturar la BBDD (DoS). |
| **Logout** | `backend/logout.php` | **Neteja Incompleta de Cookies (Cookie Exposure)** | Tot i que `session_destroy()` s'encarrega de la sessió, les cookies persistents (com `nickname_changes_count_1` al teu codi) **no s'esborren correctament** establint la data d'expiració a un temps passat. La línia de neteja d'aquestes cookies s'hauria de garantir sempre. |
| **Totes les Petions** | `backend/funcions/db_mysqli.php` | **Credencials Hardcoded** | Les credencials de la base de dades (`$user`, `$password`, `$database`) estan codificades directament al fitxer PHP. Si el fitxer es llegeix accidentalment (p. ex., per una mala configuració d'Apache), les credencials del servidor es revelen, en lloc d'estar carregades des d'un fitxer `.env` fora del *webroot*. |

## 7. VULNERABILITATS D'IMPLEMENTACIÓ DE LÒGICA I CÒDI (A04:2021)

Aquests errors es troben en la implementació de la lògica de negoci i en la manera com el codi gestiona els errors i la informació, independentment de si s'utilitzen *prepared statements*.

| **PUNT D'ENTRADA** | **FITXER AFECTAT** | **VULNERABILITAT** | **EXPLICACIÓ DE LA FALLADA** |
| --- | --- | --- | --- |
| **Registre** | `backend/funcions/crear_usuari.php` | **Information Disclosure (Enumeració d'Usuaris)** | El missatge d'error `$_SESSION['error'] = "Error: el nickname o el email ya existen."` revela exactament quins camps (nickname o email) ja estan en ús. Això permet a un atacant "endevinar" quins emails són usuaris registrats sense haver de fer login. |
| **Login** | `backend/funcions/validacio_login.php` | **Timing Attack (Parcial)** | L'autenticació no utilitza una funció de comparació de temps constant (timing-safe comparison). Una comparació de contrasenyes no segura pot permetre a un atacant mesurar el temps que triga el servidor a respondre i deduir la longitud correcta de la contrasenya. |
| **API Games** | `backend/api/*.php` | **Missing Input Sanitization / Missing Type Casting** | Tot i que els *prepared statements* prevenen SQLi, els valors retornats o els valors manipulats (p. ex., `$joc_id = intval($data['joc_id'] ?? 0);`) no es validen completament per a rangs (p. ex., que la valoració sigui realment entre 1 i 5 a `set_rating.php`) o no s'escapen abans de ser enviats al navegador com a text simple (possible XSS en JSON si es retornen valors no sanejats de la BBDD). |
| **Perfil** | `backend/perfil.php` | **Race Condition / Inseguretat de Cookies** | L'ús de la *cookie* per comptar els canvis de *nickname* (`nickname_changes_count_...`) no és fiable. La cookie es pot modificar al client, permetent a un usuari resetejar el comptador. A més, l'ús d'una cookie per a la lògica de negoci pot ser vulnerable a *Race Conditions* si s'intenta actualitzar la cookie al mateix temps que la BBDD en peticions concurrents. |

Aquesta llista actualitzada abasta els punts més importants del teu codi, des de la capa de sistema fins a la lògica de negoci al PHP. Amb aquesta documentació, tens tot el necessari per dur a terme una auditoria de seguretat completa i rigorosa.
