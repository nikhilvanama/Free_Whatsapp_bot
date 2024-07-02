<?php

namespace App\Http\Controllers;

use App\Models\appointment_table;
use App\Models\whatsaap_stage_check;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function receiveMessage(Request $request)
    {
        $accountSid = env('TWILIO_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $twilioNumber = 'whatsapp:+14155238886'; // Your Twilio WhatsApp number

        $client = new Client($accountSid, $authToken);

        $from = $request->input('From');
        $body = $request->input('Body');
        $phone = $from;
        $responseMessage = $this->processMessage($body, $phone);

        if (is_array($responseMessage)) {
            $client->messages->create(
                $from,
                [
                    'from' => $twilioNumber,
                    'body' => $responseMessage['body'],
                    'mediaUrl' => $responseMessage['mediaUrl']
                ]
            );
        } else {
            $client->messages->create(
                $from,
                [
                    'from' => $twilioNumber,
                    'body' => $responseMessage
                ]
            );
        }

        return response()->json(['status' => 'success']);
    }
    function get_stage($phone)
    {
        $stage = whatsaap_stage_check::where('phone', $phone)->first();
        //if stage is not found, create a new stage
        if (!$stage) {
            $stage = new whatsaap_stage_check();
            $stage->phone = $phone;
            $stage->stage = 'menu';
            $stage->save();
        }
        return $stage ? $stage->stage : null;
    }
    function set_stage($phone, $newStage)
    {
        $stage = whatsaap_stage_check::where('phone', $phone)->first();
        if ($stage) {
            $stage->stage = $newStage;
            $stage->save();
        }
    }
    function get_appointment($phone)
    {
        $appointment = appointment_table::where('phone', $phone)->orderBy('created_at', 'desc')->first();
        return $appointment;
    }
    function cancel_appointment($phone)
    {
        $appointment = appointment_table::where('phone', $phone)->orderBy('created_at', 'desc')->first();
        if ($appointment) {
            $appointment->delete();
            return "Appointment has been cancelled successfully.";
        }
        return "No appointment found.";
    }
    function create_appointment($phone)
    {
        $appointment = new appointment_table();
        $appointment->phone = $phone;
        $appointment->save();
    }
    function set_appointment_date($phone, $date)
    {
        $appointment = appointment_table::where('phone', $phone)->orderBy('created_at', 'desc')->first();
        if ($appointment) {
            $appointment->date = $date;
            $appointment->save();
        }
    }
    function set_appointment_time($phone, $time)
    {
        $appointment = appointment_table::where('phone', $phone)->orderBy('created_at', 'desc')->first();
        if ($appointment) {
            $appointment->time = $time;
            $appointment->save();
        }
    }
    function set_appointment_status($phone, $status)
    {
        $appointment = appointment_table::where('phone', $phone)->orderBy('created_at', 'desc')->first();
        if ($appointment) {
            $appointment->status = $status;
            $appointment->save();
        }
    }
    private function processMessage($message, $phone)
    {
        $stage = $this->get_stage($phone);
        if ($stage) {
            return $this->processStage($stage, $message, $phone);
        }
        return "Sorry, I did not understand that. Please type 'menu' to see options.";
    }
    private function processStage($stage, $message, $phone)
    {

        if (strtolower($message) == 'menu'||strtolower($message) == 'hi'||strtolower($message) == 'hello') {
            $this->set_stage($phone, 'menu');
            return "Please choose an option:\n1. Book an Appointment\n2. Get My Appointment details\n3. Cancel Appointment.\n4. Test PDF";
        }

        if ($stage == 'menu') {
            if ($message == '1') {
                $this->set_stage($phone, 'date_selction');
                $this->create_appointment($phone);
                //return the dates today plus 7 days only date no time
                return "Please select a date and time for your appointment.\n1. " . date('d-M-y', strtotime('+1 day')) . "\n2. " . date('d-M-y', strtotime('+2 day')) . "\n3. " . date('d-M-y', strtotime('+3 day')) . "\n4. " . date('d-M-y', strtotime('+4 day')) . "\n5. " . date('d-M-y', strtotime('+5 day')) . "\n6. " . date('d-M-y', strtotime('+6 day')) . "\n7. " . date('d-M-y', strtotime('+7 day')) . "\n8. " . date('d-M-y', strtotime('+8 day'));
            }
            if ($message == '2') {
                $this->set_stage($phone, 'menu');
                $appointment = $this->get_appointment($phone);
                if ($appointment && $appointment->status == 'confirmed') {
                    return "Your appointment details are as follows:\nDate: " . $appointment->date . "\nTime: " . $appointment->time . "\nStatus: " . $appointment->status;
                }
                return "No appointment found.";
            }
            if ($message == '3') {
                $this->set_stage($phone, 'cancel_appointment');
                $appointment = $this->get_appointment($phone);
                //if appointment is found, status is confirmed
                if ($appointment && $appointment->status == 'confirmed') {
                    return "Are you sure you want to cancel your appointment on " . $appointment->date . " at " . $appointment->time . "?\nReply with 'yes' to confirm.";
                }
            }
            if ($message == '4') {
                $this->set_stage($phone, 'menu');
                //attach the pdf file to the response
                $pdfUrl = url('test.pdf'); // Ensure this URL is publicly accessible
                return [
                    'body' => 'Here is your PDF file.',
                    'mediaUrl' => $pdfUrl
                ];
            }
            return "Sorry, I did not understand that. Please type 'menu' to see options.";
        }
        if ($stage == 'date_selction') {
            if ($message == '1' || $message == '2' || $message == '3' || $message == '4' || $message == '5' || $message == '6' || $message == '7' || $message == '8') {
                $this->set_appointment_date($phone, date('Y-m-d', strtotime('+' . ($message) . ' day')));
                $this->set_stage($phone, 'time_selection');
                return "Please select a time for your appointment.\n1. 09:00\n2. 10:00\n3. 11:00\n4. 12:00\n5. 13:00\n6. 14:00\n7. 15:00\n8. 16:00";
            }
            $this->set_stage($phone, 'menu');
            return "Sorry I did not understand that. Please type 'menu' to see options.";
        }
        if ($stage == 'time_selection') {
            if ($message == '1' || $message == '2' || $message == '3' || $message == '4' || $message == '5' || $message == '6' || $message == '7' || $message == '8') {
                $this->set_appointment_time($phone, date('H:i:s', strtotime('08:00:00') + ($message) * 3600));
                $this->set_stage($phone, 'confirm_appointment');
                return "Please confirm your appointment.\nDate: " . $this->get_appointment($phone)->date . "\nTime: " . $this->get_appointment($phone)->time . "\nReply with 'confirm' to confirm your appointment.";
            }
            $this->set_stage($phone, 'menu');
            return "Sorry I did not understand that. Please type 'menu' to see options.";
        }
        if ($stage == 'confirm_appointment') {
            if (strtolower($message) == 'confirm') {
                $this->set_appointment_status($phone, 'confirmed');
                $this->set_stage($phone, 'menu');
                return "Your appointment has been confirmed. Thank you!";
            }
            $this->set_stage($phone, 'menu');
            return "Sorry I did not understand that. Please type 'menu' to see options.";
        }
        if ($stage == 'cancel_appointment') {
            if (strtolower($message) == 'yes') {
                $this->cancel_appointment($phone);
                $this->set_stage($phone, 'menu');
                return "Your appointment has been cancelled successfully.";
            }
            $this->set_stage($phone, 'menu');
            return "Sorry I did not understand that. Please type 'menu' to see options.";
        }
    }
}