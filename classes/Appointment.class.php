<?php
  /**
  * Appointment
  * 
  * Class to incapsulate appointment-related data and logic
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  
  class Appointment extends Interval{
      protected $appStart;
      protected $appStop;
      protected $appDuration;
      protected $appDurationInterval;
      protected $agenda;
      protected $attendee;
      
      /**
      * Appointmen's constructor
      * 
      * @param string $start appointment start time
      * @param string $tz appointment's timezone
      * @param string $duration appointment's duration, i.e. 15m or 1h
      * @param string $agenda appointment's description
      * @param Attendee $attendee appointment's attendee
      * @return Appointment
      */
      public function __construct($start, $tz,  $duration, $agenda, Attendee $attendee){
        $this->appStart = new DateTime($start, new DateTimeZone($tz));
        
        $this->appStop = clone $this->appStart;
        if(preg_match('/^(\d+)([mhds]{1})$/', $duration, $matches)){
           $this->appDuration = $matches[1];
           $this->appDurationInterval = self::$INTERVALS[$matches[2]];  
        }
        else
            throw new Exception(sprintf("Incorrect appointment duration: %", $duration));
        $this->appStop->modify(sprintf("+%d %s", $this->appDuration, $this->appDurationInterval));
        
        $this->agenda = $agenda;
        $this->attendee = $attendee;
        
        $whStart = $attendee->getTodaysStart($this->appStart);
        $whStop = $attendee->getTodaysStop($this->appStart);
        if($this->appStart->getTimestamp() < $whStart->getTimestamp() || $this->appStart->getTimestamp() > $whStop->getTimestamp()){
            throw new Exception(sprintf("Appointment starts at %s, out of working hours for %s", $this->appStart->format('H:i:s'), $attendee->name));
        }
        
        if($this->appStop->getTimestamp() > $whStop->getTimestamp()){
            throw new Exception(sprintf("Appointment stops at %s, out of working hours for %s", $this->appStart->format('H:i:s'), $attendee->name));
        }
      }
      
      /**
      * Returns current appointment's duration in seconds
      * 
      * @return integer
      */
      public function getDurationSec(){
          return $this->appStop->getTimestamp() - $this->appStart->getTimestamp();
      }
      
      /**
      * Returns current appointment's start date
      * 
      * @return DateTime
      */
      public function getAppStart(){
          return $this->appStart;
      }      
      
      /**
      * Returns current appointment's end date
      * 
      * @return DateTime
      */
      public function getAppStop(){
          return $this->appStop;
      }
      
      /**
      * Returns current appointment's attendee
      * 
      * @return Attendee
      */
      public function getAttendee(){
          return $this->attendee;
      }
      
      /**
      * Returns current appointment's description
      * 
      * @return string
      */
      public function getAgenda(){
          return $this->agenda;
      }
  }