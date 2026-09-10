<?php

namespace Database\Seeders;

use App\Models\CoachingBooking;
use App\Models\TrainingBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingBookingsSeeder extends Seeder
{
    public function run(): void
    {
        // User ID mapping (name -> id) - using DB facade to avoid model loading issues
        $users = DB::table('users')->whereNull('archived_at')->pluck('id', 'name');
        $userMap = [];
        foreach ($users as $name => $id) {
            $userMap[$name] = $id;
            $firstName = explode(' ', $name)[0];
            $userMap[$firstName] = $id;
        }

        // Additional mappings for nicknames/short names
        $userMap['Caro'] = $userMap['Caroline'] ?? null;
        $userMap['Fiona'] = $userMap['Fiona Ciborowski'] ?? null;
        $userMap['Nils'] = $userMap['Nils Herter'] ?? null;
        $userMap['Peter'] = $userMap['Peter Scheele'] ?? null;
        $userMap['Jannika'] = $userMap['Jannika Schmidt'] ?? null;
        $userMap['Eliza'] = $userMap['Eliza Rahaus'] ?? null;

        // Booker IDs (People who create bookings)
        $jenny = DB::table('users')->where('name', 'like', 'Jennifer Temmink%')->value('id');
        $dani = DB::table('users')->where('name', 'like', 'Daniela Berg%')->value('id');
        $lucas = DB::table('users')->where('name', 'like', 'Li-Stella%')->value('id') ?? 3;
        $bene = DB::table('users')->where('name', 'like', '%Benedikt%')->value('id') ?? $jenny;
        $andreas = DB::table('users')->where('name', 'like', 'Andreas Paus%')->value('id');
        $andre = DB::table('users')->where('name', 'like', 'Andre Bergmann%')->value('id');

        // Default booker if not found
        $defaultBooker = $jenny ?? $dani ?? 3;

        // Training bookings data from screenshots
        $trainings = [
            // Screenshot 1 - Kim S
            ['user' => 'Kim Patrick Schubert', 'booker' => $jenny, 'name' => 'BVC Personal Values Assessment', 'date' => '2026-06-13', 'net_cost' => 17.91, 'status' => 'completed', 'notes' => '19,95 USD'],

            // Screenshot 2 - Jenny's team
            ['user' => 'Octavio Carneiro Azevedo', 'booker' => $jenny, 'name' => 'Dynamous AI Mastery Zugang 1Jahr', 'date' => '2026-01-21', 'net_cost' => 712, 'status' => 'completed', 'website' => 'https://dynamous.ai', 'notes' => '712 USD, Bezahlt'],
            ['user' => 'Carlos Herencia', 'booker' => $jenny, 'name' => 'Hotel - Messe AI Conference', 'date' => '2026-09-07', 'accommodation_costs' => 374, 'status' => 'pending', 'notes' => 'Reserviert & warte auf Bestätigung von Carlos'],
            ['user' => 'Carlos Herencia', 'booker' => $jenny, 'name' => 'An-/Abreise - Messe AI Conference', 'date' => '2026-09-07', 'travel_costs' => 84, 'status' => 'pending', 'notes' => 'Noch nicht gebucht & warte auf Bestätigung von Carlos'],

            // Screenshot 3 - More Jenny bookings
            ['user' => 'Stefanie Minz', 'booker' => $jenny, 'name' => 'UPW Europe', 'date' => '2026-02-25', 'net_cost' => 713.45, 'cost_gross' => 849, 'status' => 'completed'],
            ['user' => 'Caroline Neissen', 'booker' => $jenny, 'name' => 'Udemy PS', 'date' => '2026-03-05', 'net_cost' => 17.84, 'cost_gross' => 14.99, 'status' => 'completed'],
            ['user' => 'Caroline Neissen', 'booker' => $jenny, 'name' => 'OFG Adobe PS', 'date' => '2026-03-05', 'net_cost' => null, 'status' => 'on_hold', 'website' => 'https://ofg-studium.de/online-weiterbildung-adobe-photoshop/', 'notes' => 'on hold - August'],
            ['user' => 'Laura Zahn', 'booker' => $jenny, 'name' => 'ADC Festival', 'date' => '2026-04-24', 'net_cost' => 209.24, 'cost_gross' => 249, 'status' => 'completed'],
            ['user' => 'Andreas Paus', 'booker' => $andreas, 'name' => 'EBNER Media Group', 'date' => '2026-07-09', 'net_cost' => 100, 'cost_gross' => 119, 'status' => 'completed'],
            ['user' => 'Jessie Geisler', 'booker' => $dani, 'name' => 'ADC Festival Ticket', 'date' => '2026-07-14', 'net_cost' => 155.34, 'cost_gross' => 184.75, 'status' => 'completed'],
            ['user' => 'Jessie Geisler', 'booker' => $dani, 'name' => 'An-&Abreise ADC Deutsche Bahn', 'date' => '2026-07-21', 'travel_costs' => 101.39, 'cost_gross' => 108.48, 'status' => 'completed'],
            ['user' => 'Jan Kunze', 'booker' => $dani, 'name' => 'IHK Ausbilder', 'date' => '2026-10-15', 'net_cost' => 219, 'status' => 'completed'],

            // Screenshot 4 - Bene & Lucas bookings
            ['user' => 'Nils Herter', 'booker' => $bene, 'name' => 'PPC Mastery', 'date' => '2026-01-01', 'net_cost' => 99, 'status' => 'completed', 'notes' => 'mtl. aktiv'],
            ['user' => 'Peter Scheele', 'booker' => $bene, 'name' => 'Alnauten ()', 'date' => '2026-01-01', 'net_cost' => 29, 'status' => 'completed', 'notes' => 'mtl. aktiv'],
            ['user' => 'Andre Bergmann', 'booker' => $lucas, 'name' => 'Google Analytics Conference Ticket', 'date' => '2026-03-05', 'net_cost' => 503.16, 'status' => 'completed'],
            ['user' => 'Andre Bergmann', 'booker' => $lucas, 'name' => 'Google Analytics Conference Online Training', 'date' => '2026-03-05', 'net_cost' => 209.30, 'status' => 'completed'],
            ['user' => 'Anika Weingartz', 'booker' => $lucas, 'name' => 'This is Marketing Messe Frankfurt Tickets', 'date' => '2026-03-05', 'net_cost' => 238, 'cost_gross' => 283.22, 'status' => 'completed'],
            ['user' => 'Anika Weingartz', 'booker' => $lucas, 'name' => 'Züge Frankfurt This is Marketing Messe', 'date' => '2026-03-05', 'travel_costs' => 158.84, 'cost_gross' => 169.96, 'status' => 'completed'],
            ['user' => 'Anika Weingartz', 'booker' => $lucas, 'name' => 'Hotel Frankfurt', 'date' => '2026-03-09', 'accommodation_costs' => 108.76, 'status' => 'completed', 'notes' => 'Booking'],
            ['user' => 'Andre Bergmann', 'booker' => $andre, 'name' => 'Kostenerstattung, Flugticket', 'date' => '2026-03-23', 'travel_costs' => 246.99, 'status' => 'completed'],
            ['user' => 'Andre Bergmann', 'booker' => $lucas, 'name' => 'Hotel Wien', 'date' => '2026-03-26', 'accommodation_costs' => 95.76, 'status' => 'completed', 'notes' => 'Booking'],
            ['user' => 'Christoph Weiergräber', 'booker' => $defaultBooker, 'name' => 'DB Ticket 04.05.26 (50%)', 'date' => '2026-03-31', 'travel_costs' => 52.80, 'cost_gross' => 56.49, 'status' => 'completed'],
            ['user' => 'Andre Bergmann', 'booker' => $defaultBooker, 'name' => 'DB Ticket 04.05.26 (50%)', 'date' => '2026-03-31', 'travel_costs' => 52.80, 'cost_gross' => 56.49, 'status' => 'completed'],
            ['user' => 'Thomas Pilka', 'booker' => $defaultBooker, 'name' => 'DB Ticket 04.05.26 (50%)', 'date' => '2026-03-31', 'travel_costs' => 52.80, 'cost_gross' => 56.49, 'status' => 'completed'],
            ['user' => 'Andre Bergmann', 'booker' => $andre, 'name' => 'Kostenerstattung DB Ticket', 'date' => '2026-03-31', 'travel_costs' => 31.21, 'cost_gross' => 33.39, 'status' => 'completed'],
            ['user' => 'Janine Günther', 'booker' => $defaultBooker, 'name' => 'Claude Grundlagen Webinar', 'date' => '2026-06-24', 'net_cost' => 83.19, 'cost_gross' => 99, 'status' => 'completed', 'website' => 'OMR Education'],
            ['user' => 'Janine Günther', 'booker' => $defaultBooker, 'name' => 'GEO für Fortgeschrittene Seminar', 'date' => '2026-06-24', 'net_cost' => 1295, 'cost_gross' => 1541, 'status' => 'completed', 'notes' => '121 Watt'],
            ['user' => 'Sophia Berning', 'booker' => $bene, 'name' => 'Kostenerstattung Parkticket', 'date' => '2026-06-30', 'other_costs' => 10.29, 'cost_gross' => 12.25, 'status' => 'completed'],
            ['user' => 'Vanessa Klinkow', 'booker' => $defaultBooker, 'name' => 'Fahrtkostenerstattung (25.0.2026)', 'date' => '2026-06-30', 'travel_costs' => 27, 'status' => 'completed'],
            ['user' => 'Daniel Jablonski', 'booker' => $lucas, 'name' => 'Weiterbildung Analytics Mania Advanced GTM', 'date' => '2026-07-27', 'net_cost' => 525.44, 'status' => 'completed', 'notes' => 'Analytics Mania'],

            // Screenshot 5 - Daniela Berg bookings
            ['user' => 'Anke Böwer', 'booker' => $defaultBooker, 'name' => 'Kostenerstattung 09.2025', 'date' => '2026-02-13', 'other_costs' => 22.94, 'status' => 'completed', 'notes' => 'überwiesen'],
            ['user' => 'Anke Böwer', 'booker' => $defaultBooker, 'name' => 'VMA 09.2025', 'date' => '2026-02-13', 'other_costs' => 58.80, 'status' => 'completed', 'notes' => 'überwiesen'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Bahnticket', 'date' => '2026-03-03', 'travel_costs' => 118.68, 'cost_gross' => 126.98, 'status' => 'completed', 'order_number' => '723788013361', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Booking', 'date' => '2026-03-03', 'accommodation_costs' => 128, 'status' => 'completed', 'notes' => 'KK'],
            ['user' => 'Christopher Pauen', 'booker' => $defaultBooker, 'name' => 'DB Ticket 04.05.26', 'date' => '2026-03-31', 'travel_costs' => 90.63, 'cost_gross' => 96.98, 'status' => 'completed'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Unterkunft', 'date' => '2026-04-14', 'accommodation_costs' => 171.59, 'cost_gross' => 183.60, 'status' => 'completed', 'website' => 'Booking.com', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Bahnticket 17.06.', 'date' => '2026-04-14', 'travel_costs' => 124.28, 'cost_gross' => 133, 'status' => 'completed', 'notes' => 'KK'],
            ['user' => 'Christopher Pauen', 'booker' => $defaultBooker, 'name' => 'VMA', 'date' => '2026-05-13', 'other_costs' => 84, 'status' => 'completed', 'notes' => 'überwiesen'],
            ['user' => 'Tanja Glogau', 'booker' => $dani, 'name' => 'Flugticket', 'date' => '2026-05-26', 'travel_costs' => 287.25, 'status' => 'completed', 'website' => 'Lastminute.com', 'notes' => 'KK'],
            ['user' => 'Tanja Glogau', 'booker' => $dani, 'name' => 'Unterkunft', 'date' => '2026-05-27', 'accommodation_costs' => 339.76, 'status' => 'completed', 'website' => 'Booking.com', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Bahnticket 16.09.', 'date' => '2026-06-29', 'travel_costs' => 33.17, 'cost_gross' => 35.49, 'status' => 'completed', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Unterkunft', 'date' => '2026-06-29', 'accommodation_costs' => 261.93, 'status' => 'completed', 'website' => 'booking', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Bahnticket', 'date' => '2026-08-07', 'travel_costs' => 76.16, 'cost_gross' => 81.50, 'status' => 'completed', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Unterkunft', 'date' => '2026-08-08', 'accommodation_costs' => 205.92, 'status' => 'completed', 'notes' => 'KK'],
            ['user' => 'Fiona Ciborowski', 'booker' => $dani, 'name' => 'Flugticket', 'date' => '2026-08-09', 'travel_costs' => 286.29, 'status' => 'completed', 'notes' => 'KK'],

            // Screenshot 6 - Jenny bookings
            ['user' => 'Isabelle Radtke', 'booker' => $jenny, 'name' => 'OMR Education - Influencer Marketing', 'date' => '2026-01-30', 'net_cost' => 251.26, 'cost_gross' => 299, 'status' => 'completed'],
            ['user' => 'Maya Heinrichs', 'booker' => $jenny, 'name' => 'OMR', 'date' => '2026-04-01', 'net_cost' => null, 'status' => 'ordered'],
            ['user' => 'Julie Kalisch', 'booker' => $jenny, 'name' => 'OMR', 'date' => '2026-04-01', 'net_cost' => null, 'status' => 'ordered'],
            ['user' => 'Julie Kalisch', 'booker' => $jenny, 'name' => 'DB Ticket 04.05.26', 'date' => '2026-03-31', 'travel_costs' => 81.29, 'cost_gross' => 86.98, 'status' => 'completed'],
            ['user' => 'Maya Heinrichs', 'booker' => $jenny, 'name' => 'DB Ticket 04.05.26', 'date' => '2026-03-31', 'travel_costs' => 81.29, 'cost_gross' => 86.98, 'status' => 'completed'],
            ['user' => 'Eliza Rahaus', 'booker' => $jenny, 'name' => 'DMEXCO', 'date' => '2026-07-30', 'net_cost' => 199, 'status' => 'pending', 'notes' => 'über KümH - noch offen (weil finaler Preis noch nicht safe)'],
            ['user' => 'Eliza Rahaus', 'booker' => $jenny, 'name' => 'DMEXCO', 'date' => '2026-08-06', 'net_cost' => 48.11, 'cost_gross' => 51.48, 'status' => 'completed'],

            // Screenshot 7 - Dani bookings
            ['user' => 'Florian Haacks', 'booker' => $dani, 'name' => 'Bücher', 'date' => '2025-12-30', 'net_cost' => 304.37, 'status' => 'completed', 'website' => 'Amazon'],
            ['user' => 'Florian Haacks', 'booker' => $dani, 'name' => 'Jahresabo', 'date' => '2025-12-19', 'net_cost' => 114.24, 'status' => 'completed', 'website' => 'Masterclass.com'],
            ['user' => 'Florian Haacks', 'booker' => $dani, 'name' => 'Jahresabo', 'date' => '2025-12-19', 'net_cost' => 104.65, 'cost_gross' => 111.98, 'status' => 'completed', 'website' => 'blinkist.de'],
            ['user' => 'Bilgehan Bilge', 'booker' => $jenny, 'name' => 'Jahresabo', 'date' => '2026-02-10', 'net_cost' => 228.48, 'status' => 'completed', 'website' => 'Masterclass.com', 'notes' => '192 USD - bezahlt sie privat'],
            ['user' => 'Bilgehan Bilge', 'booker' => $jenny, 'name' => 'Taxi Fahrtkosten', 'date' => '2026-02-10', 'travel_costs' => 39.40, 'status' => 'completed', 'notes' => 'gebuchte Weiterbildung aus 2025 - bezahlt sie privat'],

            // Screenshot 8 - Dani bookings
            ['user' => 'Alicia Hübner', 'booker' => $dani, 'name' => 'Seminar: Wissen...', 'date' => '2026-05-11', 'net_cost' => 1260, 'cost_gross' => 1499, 'status' => 'completed', 'website' => 'https://ifm-business.de/firmen/semin', 'notes' => 'KK'],
            ['user' => 'Nadja Vitense', 'booker' => $dani, 'name' => 'AI-Weiterbildung', 'date' => '2026-08-06', 'net_cost' => 1000, 'cost_gross' => 1190, 'status' => 'completed', 'website' => 'https://ai-champions.de/programm', 'notes' => 'KK'],

            // Screenshot 9 - Jenny's own bookings
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'name' => 'BGB Club-Mitglied', 'date' => '2026-02-23', 'net_cost' => 159, 'status' => 'completed', 'website' => 'https://www.babygotbusiness.club/'],
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'name' => 'Fachliteratur Psych', 'date' => '2026-06-02', 'net_cost' => 49.44, 'cost_gross' => 52.90, 'status' => 'completed', 'website' => 'https://www.psychologie-heute.de/m'],
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'name' => 'Literatur', 'date' => '2026-06-25', 'net_cost' => 18.49, 'cost_gross' => 22, 'status' => 'completed', 'website' => 'Amazon.de'],
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'name' => 'Literatur', 'date' => '2026-06-25', 'net_cost' => 24.30, 'status' => 'completed', 'website' => 'Amazon.de'],
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'name' => 'Wispr Flow Tool', 'date' => '2026-08-05', 'net_cost' => 15, 'status' => 'completed', 'website' => 'https://wisprflow.ai/'],
            ['user' => 'Jannika Schmidt', 'booker' => $defaultBooker, 'name' => 'Buch', 'date' => '2026-08-19', 'net_cost' => 19.62, 'cost_gross' => 20.99, 'status' => 'completed', 'website' => 'Amazon.de'],
        ];

        // Insert training bookings
        foreach ($trainings as $training) {
            $userId = null;
            
            // Try to find user by full name first
            if (isset($userMap[$training['user']])) {
                $userId = $userMap[$training['user']];
            } else {
                // Try fuzzy match
                $userId = DB::table('users')->where('name', 'like', '%' . $training['user'] . '%')->value('id');
            }

            if (!$userId) {
                $this->command->warn("User not found: {$training['user']}");
                continue;
            }

            TrainingBooking::create([
                'user_id' => $userId,
                'booked_by_id' => $training['booker'] ?? $defaultBooker,
                'name' => $training['name'],
                'booking_date' => $training['date'],
                'status' => $training['status'] ?? 'completed',
                'net_cost' => $training['net_cost'] ?? null,
                'cost_gross' => $training['cost_gross'] ?? null,
                'travel_costs' => $training['travel_costs'] ?? null,
                'accommodation_costs' => $training['accommodation_costs'] ?? null,
                'other_costs' => $training['other_costs'] ?? null,
                'website' => $training['website'] ?? null,
                'order_number' => $training['order_number'] ?? null,
                'notes' => $training['notes'] ?? null,
            ]);
        }

        $this->command->info('Training bookings imported: ' . TrainingBooking::count());

        // Coaching bookings from screenshots
        $coachings = [
            // Screenshot 8 - Coaching Jakob for Nadja
            ['user' => 'Nadja Vitense', 'booker' => $dani, 'type' => 'quick_help', 'date' => '2026-02-01', 'coach_name' => 'Coaching Jakob', 'cost' => 375, 'notes' => 'überwiesen'],
            // Screenshot 9 - Coaching Dieter for Jenny
            ['user' => 'Jennifer Temmink', 'booker' => $jenny, 'type' => 'quick_help', 'date' => '2026-08-10', 'coach_name' => 'Coaching Dieter', 'cost' => null, 'notes' => '1,5 Std nach Aufwand berechnet'],
        ];

        foreach ($coachings as $coaching) {
            $userId = null;
            
            if (isset($userMap[$coaching['user']])) {
                $userId = $userMap[$coaching['user']];
            } else {
                $userId = DB::table('users')->where('name', 'like', '%' . $coaching['user'] . '%')->value('id');
            }

            if (!$userId) {
                $this->command->warn("User not found for coaching: {$coaching['user']}");
                continue;
            }

            CoachingBooking::create([
                'user_id' => $userId,
                'booked_by_user_id' => $coaching['booker'] ?? $defaultBooker,
                'booking_date' => $coaching['date'],
                'coaching_type' => $coaching['type'],
                'cost' => $coaching['cost'] ?? CoachingBooking::getCostForType($coaching['type']),
                'coach_name' => $coaching['coach_name'] ?? null,
                'notes' => $coaching['notes'] ?? null,
            ]);
        }

        $this->command->info('Coaching bookings imported: ' . CoachingBooking::count());
    }
}
