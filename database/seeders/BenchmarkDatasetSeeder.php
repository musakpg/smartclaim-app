<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModelBenchmark;

class BenchmarkDatasetSeeder extends Seeder
{
    public function run(): void
    {
        ModelBenchmark::truncate();

        $samples = [
            [
                'sample_name' => 'Petronas Dagangan Receipt #01',
                'raw_ocr_payload' => "STESEN MINYAK PETRONAS SEKSYEN 7 SHAH ALAM PRIMAX 95 RON95 RM 50.00 CASH PAYMENT THANK YOU COME AGAIN",
                'actual_category' => 'Fuel / Automotive',
                'actual_amount' => 50.00
            ],
            [
                'sample_name' => 'Starbucks Coffee Receipt #02',
                'raw_ocr_payload' => "STARBUCKS COFFEE MALAYSIA SDN BHD 1X CARAMEL MACCHIATO 16.50 1X CHICKEN PIE 8.90 TOTAL AMOUNT RM 25.40 MASTER CARD",
                'actual_category' => 'Meals & Entertainment',
                'actual_amount' => 25.40
            ],
            [
                'sample_name' => 'Popular Bookstore Supplies #03',
                'raw_ocr_payload' => "POPULAR BOOK CO (M) SDN BHD A4 PAPER 80GSM INK CARTRIDGE 680 BLACK STAPLER BULLET TOTAL RM 88.50 BAYAR CASH",
                'actual_category' => 'Office Supplies',
                'actual_amount' => 88.50
            ],
            [
                'sample_name' => 'Shell Petrol Station #04',
                'raw_ocr_payload' => "SHELL MALAYSIA TRADING SDN BHD PUMP 04 FUELS DIESEL EURO 5 RM 120.00 TOUCH N GO EWALLET",
                'actual_category' => 'Fuel / Automotive',
                'actual_amount' => 120.00
            ],
            [
                'sample_name' => 'McDonalds Drive-Thru #05',
                'raw_ocr_payload' => "GERBANG ALAF RESTAURANTS SDN BHD MCDONALDS AYAM GORENG MCD 3PC MEAL MCCHICKEN SET TOTAL DUE RM 34.20 VISA DEBIT",
                'actual_category' => 'Meals & Entertainment',
                'actual_amount' => 34.20
            ],
            [
                'sample_name' => 'MR DIY Hardware & Stationery #06',
                'raw_ocr_payload' => "MR D.I.Y. (KUCHAI LAMA) SDN BHD HIGHLIGHTER PEN TRANSPARENT TAPE SCISSORS OFFICE FILE TOTAL RM 19.90 CASH",
                'actual_category' => 'Office Supplies',
                'actual_amount' => 19.90
            ],
            [
                'sample_name' => 'Tealive Boba Beverage #07',
                'raw_ocr_payload' => "LOOB HOLDING SDN BHD TEALIVE SIGNATURE BROWN SUGAR PEARL MILK TEA ROASTED MILK TEA TOTAL RM 18.00 DUITNOW QR",
                'actual_category' => 'Meals & Entertainment',
                'actual_amount' => 18.00
            ],
            [
                'sample_name' => 'Caltex Petrol & Lubricant #08',
                'raw_ocr_payload' => "CALTEX PETROLEUM MALAYSIA RON97 HAVOLINE ENGINE OIL TOTAL RM 95.00 CREDIT CARD APPROVED",
                'actual_category' => 'Fuel / Automotive',
                'actual_amount' => 95.00
            ],
            [
                'sample_name' => 'Office Supplies & Toner #09',
                'raw_ocr_payload' => "STATIONERY ENTERPRISE HP LASERJET TONER 85A A4 PHOTO PAPER PRINTER RIBBON TOTAL RM 210.00 CASH PAYMENT",
                'actual_category' => 'Office Supplies',
                'actual_amount' => 210.00
            ],
            [
                'sample_name' => 'KFC Restaurant Dinner #10',
                'raw_ocr_payload' => "QSR STORES SDN BHD KFC RESTAURANT 9PC HOLIDAY BUCKET CHEEZY WEDGES DRINKS TOTAL RM 62.90 CASH",
                'actual_category' => 'Meals & Entertainment',
                'actual_amount' => 62.90
            ],
        ];

        foreach ($samples as $item) {
            ModelBenchmark::create([
                'sample_name' => $item['sample_name'],
                'raw_ocr_payload' => $item['raw_ocr_payload'],
                'actual_category' => $item['actual_category'],
                'actual_amount' => $item['actual_amount'],
            ]);
        }
    }
}