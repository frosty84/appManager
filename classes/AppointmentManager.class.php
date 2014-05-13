<?php
  /**
  * Main Appointment manager
  * 
  * Loads all invitees, processes all appointments
  * Allows to search for open time slots
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  
  class AppointmentManager extends Interval{
      protected $attendees;
      protected $appointments;
      protected $tfStart;
      protected $tfStop;
      protected $timeSlots = array();
      protected $meetingLength;
      protected $meetingLengthMeasureUnits;
      protected $numOfSlots;
      
      /**
      * Applies work hours and existent appointments data to arranged list of time slots.
      * Invalidates each time slot which is not available for at least one invitee
      */
      public function applyExistentAppointments(){
          foreach($this->timeSlots as $key => $slot){
              $slot->setAllAttendeesValid($this->attendees);
              foreach($this->attendees as $attendee){                                                              
                  $whStart = $attendee->getTodaysStart($slot->start);
                  $whStop = $attendee->getTodaysStop($slot->start);
                  
                  if($slot->start->getTimestamp() < $whStart->getTimestamp()){ #slot starts before working hours of this attendee
                    $this->timeSlots[$key]->invalidateFor($attendee, 'starts before working hours');
                  }
                  
                  if($slot->start->getTimestamp() >= $whStop->getTimestamp()){ #slot starts after working hours of this attendee
                    $this->timeSlots[$key]->invalidateFor($attendee, 'starts after working hours');
                  }
                  
                  if($slot->stop->getTimestamp() > $whStop->getTimestamp()){ #slot stops after working hours of this attendee
                    $this->timeSlots[$key]->invalidateFor($attendee, 'stops after working hours');
                  }
              }
              
              foreach($this->appointments as $appointment){
                  #skip empty (no duration) appointments
                  if($appointment->getDurationSec() <= 0)
                    continue;
                  
                  if($appointment->getAppStart()->getTimestamp() <= $slot->start->getTimestamp() && $appointment->getAppStop()->getTimestamp() >= $slot->stop->getTimestamp()){
                      #appointment begins before current slot and ends out of it
                      $this->timeSlots[$key]->invalidateFor($appointment->getAttendee(), 'booked by appointment (overall): '.$appointment->getAgenda());
                  }
                  elseif($appointment->getAppStart()->getTimestamp() >= $slot->start->getTimestamp() && $appointment->getAppStart()->getTimestamp() < $slot->stop->getTimestamp()){
                      #appointment begins somewhere in current slot
                      $this->timeSlots[$key]->invalidateFor($appointment->getAttendee(), 'booked by appointment (start): '.$appointment->getAgenda());
                  }
                  elseif($appointment->getAppStop()->getTimestamp() > $slot->start->getTimestamp() && $appointment->getAppStop()->getTimestamp() <= $slot->stop->getTimestamp()){
                      #appointment ends somewhere in current slot
                      $this->timeSlots[$key]->invalidateFor($appointment->getAttendee(), 'booked by appointment (stop): '.$appointment->getAgenda());    
                  }
              }
          }
      }
      
      /**
      * method to compare two time slots basing on number of incompatible ateendees
      * 
      * @param TimeSlot $a
      * @param TimeSlot $b
      */
      public static function compareTimeSlots(TimeSlot $a, TimeSlot $b){
          $res;
          if($a->getNumberOfIssues() == $b->getNumberOfIssues()){
              $res = 0;
          }
          else{
              $res = $a->getNumberOfIssues() < $b->getNumberOfIssues() ? -1 : 1;
          }
          return $res;
      }
      
      /**
      * AppointmentManager's constructor
      * 
      * @param string $tfStart time frame start
      * @param string $tfStop time frame stop 
      * @param string $tz timezone
      * @param integer $mLength meeting length
      * @param integer $numSlots number of possible time-slots that should be found
      * @return AppointmentManager
      */
      public function __construct($tfStart, $tfStop, $tz, $mLength, $numSlots){
        $this->tfStart = new DateTime($tfStart, new DateTimeZone($tz));
        $this->tfStop = new DateTime($tfStop, new DateTimeZone($tz));
        
        $test = $this->tfStart->format('Y-m-d H:i:sP');
        
        $this->numOfSlots = (int) $numSlots;
        if(preg_match('/^(\d+)([mhds]{1})$/', $mLength, $matches)){
           $this->meetingLength = $matches[1];
           $this->meetingLengthMeasureUnits = self::$INTERVALS[$matches[2]];  
        }
        else
            throw new Exception(sprintf("Incorrect meeting length: %s", $mLength));
        
        if(($this->tfStop->getTimestamp() - $this->tfStart->getTimestamp()) <= 0){
            throw new Exception(sprintf('Invalid time frame from %s to %s', $this->tfStart->format('Y-m-d H:i:s'), $this->tfStop->format('Y-m-d H:i:s')));
        }
        
        $interval = DateInterval::createFromDateString(sprintf('%d %s', $this->meetingLength, $this->meetingLengthMeasureUnits)); #30 minutes
        $slots    = new DatePeriod($this->tfStart, $interval, $this->tfStop);
        foreach ($slots as $slot) {
            $start = clone $slot;
            $stop =  $slot->modify(sprintf('+%d %s', $this->meetingLength, $this->meetingLengthMeasureUnits));
            $this->timeSlots[] = new TimeSlot($start, $stop);
        }
      }
      
      /**
      * Loads appointments and invitees
      * 
      * @param array $appointments list of appointments
      */
      public function load($appointments = array()){
         foreach($appointments as $appointment){
            $this->appointments[] = $appointment;
            $attendee = $appointment->getAttendee();
            $this->attendees[$attendee->name] = $attendee;
         }  
      }
      
      /**
      * Get array of all available time slots
      * 
      * @return array of available TimeSlot
      */
      public function getAvailableTimeSlots(){
        $availableSlots = array();
        foreach($this->timeSlots as $slot){
            if($slot->isAvailable()){
                $availableSlots[] =  $slot;
            }
        }  
        return $availableSlots;
      }
      
      /**
      * Get array time slots sorted by availability
      * 
      * @return array of available TimeSlot
      */
      public function getMostAppropriateTimeSlots(){
          $appropriateSlots = array();
          $timeSlots = $this->timeSlots;
          usort($timeSlots, array('AppointmentManager', 'compareTimeSlots'));
          return $timeSlots;
      }
      
      /**
      * Returns meeting length as human readable string
      * 
      * @return string
      */
      public function getLength(){
          return sprintf("%d %s", $this->meetingLength, $this->meetingLengthMeasureUnits);
      }
      
      /**
      * Returns number of required time slots to find
      * 
      * @return integer
      */
      public function getRequestedSlotsNum(){
          return $this->numOfSlots;
      }
      
      /**
      * Returns time-frame period start
      * 
      * @return DateTime
      */
      public function getTfStart(){
          return $this->tfStart;
      }
      
      /**
      * Returns time-frame period end
      * 
      * @return DateTime
      */
      public function getTfStop(){
          return $this->tfStop;
      }
  }
