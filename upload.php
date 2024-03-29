
  <?php 
 require 'functions.php';

  // on se connecte à la base de données
  $localhost = "localhost"; 
  $dbusername = "root"; 
  $dbpassword = "";  
  $dbname = "images";  
  $pdo = new PDO("mysql:host=$localhost;dbname=$dbname", $dbusername, $dbpassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  

  // Vérifiez si le formulaire a été soumis
  if(isset($_POST['submit'])) {

  foreach($_FILES['image']['tmp_name'] as $key => $tmp_name) {
    // Récupérez les données de l'image
    $image_data = file_get_contents($_FILES['image']['tmp_name'][$key]);
  
    // Récupérez les métadonnées de l'image
    $exif = exif_read_data($_FILES['image']['tmp_name'][$key]); 
  
    // Récupérez les informations de l'image
    $image_name = $_FILES['image']['name'][$key];
    $model = isset($exif['Model']) ? $exif['Model'] : 'Inconnu';
    $marque = isset($exif['Make']) ? $exif['Make'] : 'Inconnu';
    $poid = $exif['FileSize'] ? $exif['FileSize'] : 0;
        // si la date n'est pas disponible, utilisez la date de modification de l'image tout en convertissant la date en format UNIX
        $date = $exif['DateTimeOriginal'] ? $exif['DateTimeOriginal'] : date('Y-m-d H:i:s', filemtime($_FILES['image']['tmp_name'][$key]));

        // Récupérez les données GPS de l'exif
        $gpslat = $exif['GPSLatitude'] ? $exif['GPSLatitude'] : 'Inconnu';
        $gpslong = $exif['GPSLongitude'] ? $exif['GPSLongitude'] : 'Inconnu';

        // Convertissez les données GPS en un format lisible que si les données GPS sont disponibles
        if($gpslat != 'Inconnu' ) {
          $gpslat = gps2Num($gpslat[0], $gpslat[1], $gpslat[2]);

        }
        if($gpslong != 'Inconnu' ) {
          $gpslong = gps2Num($gpslong[0], $gpslong[1], $gpslong[2]);
        }

        // pour acceuillir les coordonnées GPS en degrés décimaux dans la base de données nous devons avoir des colonnes de type float

        //taille image hauteur x largeur
        $taille = $exif['COMPUTED']['Height'] . ' x ' . $exif['COMPUTED']['Width'] ? $exif['COMPUTED']['Height'] . ' x ' . $exif['COMPUTED']['Width'] : 'Inconnu';

        //on check si l'image existe déjà dans la base de données
        $image_name = $_FILES['image']['name'][$key];

        $sql2 = "SELECT * FROM images WHERE name = :image_name";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam(':image_name', $image_name);
        $stmt2->execute();
        $result = $stmt2->fetch();

        if ($result) {
          // comme nous sommes dans une boucle, nous devons utiliser continue pour passer à l'image suivante mais si il n'y a pas d'image suivante, le script s'arrête, des que la boucle est terminée, nous sommes redirigés vers la page d'accueil
          continue;

        }

        // Préparez la requête SQL pour insérer l'image dans la base de données
        $sql = "INSERT INTO images (image, name, camera_model, brand, weight, created_at, gps_position_lat,gps_position_long, size) VALUES (:image, :image_name, :model, :marque, :poid, :date, :gpslat, :gpslong, :taille)";
        $stmt = $pdo->prepare($sql);

        // Liez les données de l'image à la requête SQL
        $stmt->bindParam(':image', $image_data, PDO::PARAM_LOB);
        $stmt->bindParam(':image_name', $image_name);
        $stmt->bindParam(':model', $model);
        $stmt->bindParam(':marque', $marque);
        $stmt->bindParam(':poid', $poid);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':gpslat', $gpslat);
        $stmt->bindParam(':gpslong', $gpslong);
        $stmt->bindParam(':taille', $taille);

        // Exécutez la requête SQL
        if($stmt->execute()) {
          echo "Image téléchargée avec succès.";
        }
      
  }
  header("Location: index.php");
  exit;
}
  

  ?>

  