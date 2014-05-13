<?php

/**
* Appointment Manager test script (commandline)
* 
* Appointment manager was created as part of test interview task.
* Required PHP version: >= 5.3.23
* To run a manager on test data set, just run ./appManager/appManager.php
* Test data set can be found in ./appManager/inputData.yaml
* 
* @author Vitalii Bondarenko 
* @version  0.2
*/

require_once "./bootstrap.php";
require_once "./spyc/spyc.php";
$data = Spyc::YAMLLoad('./inputData.yaml');

try{
    #create appointment manager object
    $manager = new AppointmentManager(  $data['requested_time_frame']['start'], 
                                        $data['requested_time_frame']['stop'], 
                                        $data['requested_time_frame']['tz'],
                                        $data['requested_meeting_length'],
                                        $data['requested_time_slots']);
    foreach($data['attendees'] as $attendee){
            $appointments = array();
            $attendeeObj = new Attendee($attendee['name'], 
                                        $attendee['working_time_start'], 
                                        $attendee['working_time_stop'],
                                        $attendee['tz']); 
                                        
            foreach($attendee['existing_appointments'] as $appointment){
                    $appointments[] = new Appointment(  $appointment['start'],                    
                                                        $appointment['tz'],
                                                        $appointment['duration'],
                                                        $appointment['agenda'],
                                                        $attendeeObj);
            }
            $manager->load($appointments);    
    }
    $manager->applyExistentAppointments();
    $timeSlots = $manager->getAvailableTimeSlots();

    printf("Requested %d of %s-length slots for period from %s to %s\n", $manager->getRequestedSlotsNum(), $manager->getLength(), $manager->getTfStart()->format('Y-m-d H:i:sP'), $manager->getTfStop()->format('Y-m-d H:i:sP'));
    if(count($timeSlots)){
        $max = count($timeSlots) >= $manager->getRequestedSlotsNum() ? $manager->getRequestedSlotsNum() : count($timeSlots);
        printf("Following %d time slots were found:\n", $max);
        for($i = 0; $i < $max; $i++){
            $slot = $timeSlots[$i];
            printf("From %s to %s\n", $slot->start->format('Y-m-d H:i:s'), $slot->stop->format('Y-m-d H:i:s'));
        }
    }
    else{
        print "There are no available slots, unfortunately...\n";
        $appropriateSlots = $manager->getMostAppropriateTimeSlots();
        if(count($appropriateSlots)){
            print "However, following slots are available for most of invitees:\n";
            $i = 0;
            while($i < 3){
                if($slot = $appropriateSlots[$i]){
                    printf("From %s to %s\n", $slot->start->format('Y-m-d H:i:s'), $slot->stop->format('Y-m-d H:i:s'));
                    if(is_array($slot->availableFor) && count($slot->availableFor)){
                        printf("\t#available for %s\n", join(", ", array_keys($slot->availableFor)));
                    }
                    if(is_array($slot->notAvailableFor) && count($slot->notAvailableFor)){
                        foreach($slot->notAvailableFor as $attendee)
                            printf("\t#n/a for %s (%s)\n", $attendee['attendee'], $attendee['reason']);
                    }
                    $i++;
                }
                else
                    $i = 3;
            }
        }
    }
}
catch(Exception $e){
    printf("Exception: %s\n", $e->getMessage());
    print "Inconsistant data, see exception message above. Can't proceed\n";
}