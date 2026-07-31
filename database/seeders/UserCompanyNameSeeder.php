<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserCompanyNameSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'aerizozambia@gmail.com'            => 'Aerizo Industries Limited',
            'audrey@ahc.group'                  => 'African Horizon Construction',
            'talent@ahc.group'                  => 'African Horizon Construction Company',
            'laboratorysolution09@gmail.com'    => 'Analytical World Ltd Attention Siyahamba Wasa Namala',
            'munuwal@gmail.com'                 => 'Angevine Investments',
            'engineering.appletree@gmail.com'   => 'Appletree Engineering',
            'buying@hotali.co.zw'               => 'Architectural Aluminium Pvt Ltd',
            'jeremyk@mahindraauto.co.zw'        => 'Asia Auto (Pvt) Ltd/ Jeremy',
            'accounts@veldemeers.com'           => 'Baba Vedu Enterprises Pvt Ltd T/A Cafe Veldemeers',
            'benmlambo2011@gmail.com'           => 'BANELLY DAKARAI MARKETING MARKETING',
            'mandymakazinge4@gmail.com'         => 'Bigkap Marketing',
            'bldsups@gmail.com'                 => 'Bl&D Suppliers',
            'buying@canterburymining.com'       => 'Canterbury Mining Private Limited',
            'dmusengezi@capitallaser.co.zw'     => 'Capital Laser Engineering',
            'wchipaumire@capitallaser.co.zw'    => 'Capital Laser Engineering',
            'info@caterwarehouse.co.zm'         => 'Cater Warehouse Limited',
            'info@charryevents.co.zw'           => 'Charry Event Solutions Pvt Ltd',
            'huxianghai6@gmail.com'             => 'Citibay Enterprises Private Limited',
            'craikun5@gmail.com'                => 'Craikun Incoporation',
            'brightonb82@gmail.com'             => 'Dalkeith Engineering',
            'anthony.mandigona@dandemutande.africa' => 'Dandemutande Investments',
            'nesisa.khupe@datlabs.co.zw'        => 'Datlabs Pvt Ltd',
            'procurement@deveneng.co.zw'        => 'Deven Engineering',
            'ernestnkenterpriseslimited@gmail.com' => 'Ernest Nk Enterprises Limited',
            'admin@mvimi.com'                   => 'Erthax Investments',
            'melanieh@esseclearing.com'         => 'Esse Clearing Limited',
            'stores@execair.co.zw'              => 'Executive Air (Pvt) Ltd',
            'nelcotembo@gmail.com'              => 'Foodies Pvt Ltd',
            'memory.phiri@fxlogistics.biz'      => 'Fx Logistics',
            'accounts@geopomona.com'            => 'Geo Pomoma Waste Management',
            'admin@ginomai.net'                 => 'Ginomai Africa Pvt Ltd',
            'cecilia.chandireva@greenfuel.co.zw' => 'Greenfuel Pvt Ltd',
            'muripo80@gmail.com'                => 'Gulfzone Trading',
            'chii.chakaipa@icecash.co.zw'       => 'Ice Cash Pvt(Ltd)',
            'josephd@bakersinnzim.com'          => 'Innscor Africa Limited T/A Bakers Inn Manufacturing',
            'admin@inversetechnologies.com'     => 'Inverse Technologies',
            'iwr@zim.co.zw'                     => 'Iwr Pvt Ltd',
            'mikeseager47@gmail.com'            => 'Jecha Rakanaka Pvt Ltd',
            'kayla@kenkosa.co.za'               => 'Kenko Sa Pty Ltd',
            'kolabenergy@gmail.com'             => 'Kolab Energy Pvt Ltd',
            'natasha.chikoti@masimbagroup.com'  => 'Masimba Construction',
            'medchemzam@gmail.com'              => 'Medchem Central Limited',
            'info@mic.co.zw'                    => 'Mic Radiology Group',
            'arunmooljee@yahoo.com'             => 'Mooljee Investments Llc',
            'accounts@mwakabanga.com'           => 'Mwakabanga Investments Limited',
            'accounts@indigomining.com'         => 'Mwami Resources Pvt Ltd',
            'finance@homeimprovements.co.zw'    => 'Neywel Trading Pvt Ltd',
            'sales@nitapel.co.zw'               => 'Nitapel Enterprises',
            'gary@nutrimaster.co.zw'            => 'Nutrimaster (Pvt) Ltd',
            'kudzai.mutongwizo@nyaradzo.co.zw'  => 'Nyaradzo Group',
            'tinayetarusikirwa@gmail.com'        => 'Olive Oasis Pvt Ltd',
            'admin@onetouch.co.zw'              => 'One Touch Electrical Services (Pvt) Ltd',
            'pcmzambialtd@gmail.com'            => 'Pcm Zambia Limited',
            'sme@peterhouse.co.zw'              => 'Peterhouse Group Of Schools',
            'accounts@pivotalagro.com'          => 'Pivotal Agro-Services (Pvt) Ltd',
            'garywoolleyzim@gmail.com'          => 'Prostruct Construction',
            'randhir@uniturtle.com'             => 'Randhir H. Patel C/O. Uniturtle Ind. Ltd.',
            'sales@repoquad.com'                => 'Repoquad Investments',
            'pbanda@riftvalley.com'             => 'Rift Valley Properties',
            'stillemeer.kotze75@gmail.com'      => 'Rotterdom Trading',
            'eviemutumbu89@gmail.com'           => 'Royal Magi Investments',
            'sabricorptrading@gmail.com'        => 'Sabricorp Trading',
            'alegrange@damarabioagri.com'       => 'Saiwit Holdings (Pvt) Ltd',
            'storeeymarketing@gmail.com'        => 'Storey Marketing',
            'info@travesinvestments.com'        => 'Traves Investments Ltd',
            'turnpikeminingsupplies@gmail.com'  => 'Turnpike Mining Supplies',
            'tech-admin@veliqo.com'             => 'Veliqo Lab Solutions Pvt Ltd',
            'yvettenomalanga12@gmail.com'       => 'Vet Farm Solutions',
            'voticeengineering@gmail.com'       => 'Votice Engineering',
            'kalio.maleya@zambeef.co.zm'        => 'Zambeef Plc',
            'sales@ziibesttyresupplies.com'     => 'Ziibest Tyres Supplies',
            'sales@ziibesttyresuppliers.com'    => 'Ziibest Tyres Supplies',
            'purchases@zimgold.com'             => 'Zimgold Oil Industries Pvt Ltd',
            'mail@goldybytes.com'               => 'Zohar Enterprises Pvt Ltd',
        ];

        $updated = 0;
        $notFound = [];

        foreach ($companies as $email => $companyName) {
            $rows = DB::table('users')
                ->where('email', $email)
                ->update(['company_name' => $companyName]);

            if ($rows > 0) {
                $updated++;
            } else {
                $notFound[] = $email;
            }
        }

        $this->command->info("Updated company_name for {$updated} user(s).");

        if (!empty($notFound)) {
            $this->command->warn('No user found for the following email(s):');
            foreach ($notFound as $email) {
                $this->command->line("  - {$email}");
            }
        }
    }
}
