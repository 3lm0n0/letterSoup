<?php
class Word {
    const HORIZONTAL = 0;
    const VERTICAL = 1;
    const DIAGONAL_LEFT_TO_RIGHT = 2;
    const DIAGONAL_RIGHT_TO_LEFT = 3;

    private $_orientation; // Orientación de la palabra
    private $_label; // Título de la palabra
    private $_reversed; // Etiqueta invertida o no(true,false)
    private $_start; // Índice de la caja de salida
    private $_end; // Índice de la caja de llegada

    public function __construct($start,$end,$orientation,$label,$reversed) {
      $this->_start=$start;
      $this->_end=$end;
      $this->_orientation=$orientation;
      $this->_label=$label;
      $this->_reversed=$reversed;
		}

    public function setStart($start) {
        $this->_start=$start;
        }

    public function setEnd($end) {
        $this->_end=$end;
        }

    public function setLabel($label) {
        $this->_label=$label;
        }

    public function setReverse($reverse) {
        $this->_reversed=$reverse;
        }

    public function getStart() {
        return $this->_start;
        }

    public function getEnd() {
        return $this->_end;
        }

    public function getLabel() {
        return $this->_label;
        }

    public function isReversed() {
        return $this->_reversed;
        }

    public function getOrientation() {
        return $this->_orientation;
        }

    } // class Word
?>
