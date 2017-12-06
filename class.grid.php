<?php
class Grid  {
    const MIN_LEN_WORD = 3; // Longitud mínima de las palabras en la base
    const MAX_LEN_WORD = 10 ; // Longitud máxima de las palabras en la base
    const DEFAULT_GRID_SIZE = 10; // Tamaño de cuadrícula predeterminado

    const RENDER_HTML = 0; // Mostrar cuadrícula en HTML
    const RENDER_TEXT = 1; // Mostrar cuadrícula en la vista texto

    private $_size; // (Int) Longitud del lado cuadrado de la cuadrícula
    private $_cells; // (tabla de tamaño * elementos de cadena de tamaño) celdas de cuadrícula, cada una contiene una letra
    private $_wordsList; // (tabla de objetos de Word): lista de palabras para encontrar
    private $_arrayCOL; // Tabla (int) de números de columna basados en índices de celdas
    private $_db; // Base de datos MySQL
    private $_errorMsg; // Cadena de texto que no es cero en caso de error

    public function __construct($size=self::DEFAULT_GRID_SIZE) {
      $this->_errorMsg='';
      if($size<self::MIN_LEN_WORD || $size<self::MAX_LEN_WORD) {
        $this->_errorMsg='size must be between '.self::MIN_LEN_WORD.' and '.self::MAX_LEN_WORD;
        echo $this->_errorMsg;
        return;
      }
      $this->_size=$size;
      $this->_wordsList=array();
      $this->_cells=array_fill(0,$this->_size*$this->_size,'');
      $this->_db = new SQLite3('words.db',SQLITE3_OPEN_READONLY);
      // Índice de columnas en 2 cuadrículas de fijación vertical
      // Para gestionar el desbordamiento en la parte inferior
      $this->_arrayCOL = array();
      for($i=0;$i<(2*$this->_size*$this->_size);$i++) {
        $this->_arrayCOL[$i]=self::COL($i);
      }
		}

    public function __destruct() {
        if($this->_errorMsg!='') {return;}
        $this->_db->close();
        }

    public function getWordsList($end=' ') {
        // Devuelve la lista de palabras que se encuentran en la cuadrícula en orden alfabético.
        // $end : Separador de palabras definido por el usuario (\n, <br>, space...)

        if($this->_errorMsg!='') {return;}
        $arr=array();
        foreach($this->_wordsList as $word) {
            $label=$word->getLabel();
            if($word->isReversed()) {$label=strrev($label);}
            $arr[]=$label;
            }
        sort($arr);
        $r='';
        foreach($arr as $label) {
            $r.=$label.$end;
            }
        return $r;
        }

    public function getNbWords() {
        return count($this->_wordsList);
        }

    public function gen() {
        // Crea una nueva cuadrícula

        if($this->_errorMsg!='') {return;}

        $size2=$this->_size*$this->_size;
        $i=rand(0,$size2-1); // Dejamos una caja al azar

        // Vamos a través de todas las cajas
        $cpt=0;
        while($cpt<$size2) {
            $this->placeWord($i);
            $cpt++;
            $i++;
            if($i==$size2) {$i=0;}
            }
        } // gen()

    private function placeWord($start) {
        // Intenta colocar una palabra en el cuadro de inicio $Start, con :
        // - horizontal,vertical,diagonal
        // - Invertido

        // Nueva palabra, caja de inicio dada en parametro ($start)
        $word=new Word(
            $start, // Índice de la caja de salida
            -1, // Final, vamos a ver más bajo de acuerdo a la orientación y la longitud de la palabra
            rand(0,3), // orientation
            '', // Redacción dibujado en el último momento
            (rand(0,1) == 1) // Invertido : true o false al azar
            );

        $inc=1; // Incremento
        $len=rand(self::MIN_LEN_WORD,$this->_size); // Longitud de una palabra aleatoria, de MIN_LEN_WORD a _size

        switch($word->getOrientation()) {

            case Word::HORIZONTAL:
                $inc=1;
                $word->setEnd($word->getStart()+$len-1);
                 // Si la palabra colocada en 2 líneas cambia a la izquierda
                while( $this->_arrayCOL[$word->getEnd()] < $this->_arrayCOL[$word->getStart()] ) {
                    $word->setStart($word->getStart()-1);
                    $word->setEnd($word->getStart()+$len-1);
                    }
                break;

            case Word::VERTICAL:
                $inc=$this->_size;
                $word->setEnd($word->getStart()+($len*$this->_size)-$this->_size);
                // Si la palabra pasa por la rejilla inferior, cambiamos
                while($word->getEnd()>($this->_size*$this->_size)-1) {
                    $word->setStart($word->getStart()-$this->_size);
                    $word->setEnd($word->getStart()+($len*$this->_size)-$this->_size);
                    }
                break;

            case Word::DIAGONAL_LEFT_TO_RIGHT:
                $inc=$this->_size+1;
                $word->setEnd($word->getStart()+($len*($this->_size+1))-($this->_size+1));
                // Si la palabra excede la cuadrícula a la derecha, cambiamos a la izquierda
                while( $this->_arrayCOL[$word->getEnd()] < $this->_arrayCOL[$word->getStart()] ) {
                    $word->setStart($word->getStart()-1);
                    $word->setEnd($word->getStart()+($len*($this->_size+1))-($this->_size+1));
                    }
                // Si la palabra pasa por la rejilla inferior, cambiamos
                while($word->getEnd()>($this->_size*$this->_size)-1) {
                    $word->setStart($word->getStart()-$this->_size);
                    $word->setEnd($word->getStart()+($len*($this->_size+1))-($this->_size+1));
                    }
                break;

            case Word::DIAGONAL_RIGHT_TO_LEFT:
                $inc=$this->_size-1;
                $word->setEnd($word->getStart()+(($len-1)*($this->_size-1)));
                // si le mot sort de la grille à gauche, on décale à droite
                while( $this->_arrayCOL[$word->getEnd()] > $this->_arrayCOL[$word->getStart()] ) {
                    $word->setStart($word->getStart()+1);
                    $word->setEnd($word->getStart()+(($len-1)*($this->_size-1)));
                    }
                // Si la palabra pasa por la rejilla inferior, cambiamos
                while($word->getEnd()>($this->_size*$this->_size)-1) {
                    $word->setStart($word->getStart()-$this->_size);
                    $word->setEnd($word->getStart()+(($len-1)*($this->_size-1)));
                    }
                break;
            }

        // Construimos el patrón SQL ("A__O___") Si la palabra cruza letras en la cuadrícula
        $s='';
        $flag=false;
        for($i=$word->getStart();$i<=$word->getEnd();$i+=$inc) {
          if($this->_cells[$i]=='') {$s.='_';}
          else {
            $s.=$this->_cells[$i];
            $flag=true;
          }
        }

        // Si encontramos que '_' => Sin solapamiento añadimos la palabra
        if(!$flag) {
          $word->setLabel($this->getRandomWord($len)); // Uno tiene que dibujar una palabra de longitud len
          if($word->isReversed()) {$word->setLabel(strrev($word->getLabel()));}
          $this->addWord($word);
        }

        // contrario
        else {
            // Si el patrón es un texto completo se deja
            if(strpos($s,'_')===false) {return;}

            // on en pioche un avec ce pattern
            $word->setLabel($this->getWordLike($s));
            $word->setReverse(false); // La nueva palabra escogida no se invierte

            //  Agregar la palabra (test null in addmot)
            $this->addWord($word);
            }

        } // placeWord()

    public function render($type=Grid::RENDER_HTML) {
        // Muestra la cuadrícula completa en el formato
        // Texto o HTML (predeterminado)

        if($this->_errorMsg!='') {return;}

        $r='';

        switch($type) {
            case Grid::RENDER_HTML:
            $cpt=0;
            $r.='<style type="text/css">
                table.gridtable {
                    font-family: verdana,arial,sans-serif;
                    font-size:16px;
                    color:#fff;
                    border-width: 1px;
                    border-color: #fff;
                    border-collapse: collapse;
                    }
                table.gridtable td {
                    border-width: 1px;
                    padding: 8px;
                    border-style: solid;
                    border-color: #fff;
                    background-color: #666666;
                    }
                </style>'.PHP_EOL;
            $r.='<table class="gridtable">'.PHP_EOL;
            foreach($this->_cells as $letter) {
                if($cpt==0) {$r.='<tr>';}
                if($letter=='') {$r.='<td>'.chr(rand(65,90)).'</td>';}
                else {$r.='<td>'.$letter.'</td>';}
                $cpt++;
                if($cpt==$this->_size) {$r.='</tr>'.PHP_EOL; $cpt=0;}
                }
            $r.='</table>'.PHP_EOL;
            break;

            case Grid::RENDER_TEXT:
            $cpt=0;
            foreach($this->_cells as $letter) {
                if($letter=='') {$r.=chr(rand(65,90));}
                else {$r.=$letter;}
                $r.=' ';
                $cpt++;
                if($cpt==$this->_size) {$r.="\n"; $cpt=0;}
                }
            break;
            }

        return $r;
        }

    private function COL($x) {
        // IN : (int $x) = Índice de la caja
        // OUT : (int) número de la columna, de 1 a $this->_size
        return ($x % $this->_size)+1;
        }

    private function getRandomWord($len) {
        // IN (Int) : Longitud de la palabra $len
        // OUT (String) : Una palabra de longitud aleatoria $len
        $rqtxt='SELECT word FROM words WHERE LENGTH(word)='.$len.' ORDER BY RANDOM() LIMIT 1';
        return $this->_db->querySingle($rqtxt);
        }

    private function getWordLike($pattern) {
        // Devuelve una palabra que se asemeja al patrón.
        // IN (String) : $pattern, ex : A__U__S
        // OUT (String) : Una palabra aleatoria que coincida, sino ""
        $rqtxt='SELECT word FROM words WHERE word LIKE "'.$pattern.'" ORDER BY RANDOM() LIMIT 1';
        return $this->_db->querySingle($rqtxt);
        }

    private function addWord($word) {
        // Agregar una palabra :
        // - En las cajas de cuadrícula
        // - A la lista de palabras que se encuentran

        if($word->getLabel()=='') {return;}

        // Agregar a cuadros de cuadrícula
        $j=0;
        switch($word->getOrientation()) {

            case Word::HORIZONTAL:
                for ($i=$word->getStart(); $j<strlen($word->getLabel()); $i++) {
                    $this->_cells[$i]=substr($word->getLabel(),$j,1);
                    $j++;
                    }
                break;

            case Word::VERTICAL:
                for($i=$word->getStart(); $j<strlen($word->getLabel()); $i+=$this->_size) {
                    $this->_cells[$i]=substr($word->getLabel(),$j,1);
                    $j++;
                    }
                break;

            case Word::DIAGONAL_LEFT_TO_RIGHT:
                for($i=$word->getStart(); $j<strlen($word->getLabel()); $i+=$this->_size+1) {
                    $this->_cells[$i]=substr($word->getLabel(),$j,1);
                    $j++;
                    }
                break;

            case Word::DIAGONAL_RIGHT_TO_LEFT:
                for($i=$word->getStart(); $j<strlen($word->getLabel()); $i+=$this->_size-1) {
                    $this->_cells[$i]=substr($word->getLabel(),$j,1);
                    $j++;
                    }
                break;

            } // switch

        // Agregar la palabra a la lista
        $this->_wordsList[]=$word;

        } // addWord()

    } // class Grid
?>
