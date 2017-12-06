<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Letter Soup</title>
</head>
  <body>
      <?php
      require_once 'class.grid.php';
      require_once 'class.word.php';

      $grid=new Grid();
      $grid->gen();
      echo $grid->render();
      echo $grid->getNbWords()." words to find: \n";
      echo $grid->getWordsList(", ");
      ?>
    </body>
</html>
