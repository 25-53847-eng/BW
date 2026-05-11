<?php
require 'db_config.php';

// User's authoritative list
$authoritative = [
    "5 Axis International Corporation","Abesco Construction Development Corp.","ACAA Inc.","AME Construction & Gen. Engineering Services","Andison Manila Use","Archonell Inc.","Argotek Innovative Exchange, Inc.","Ascof Construction. Inc.","Assistco Energy & Industrial Corporation","Bolintaguen Cons and Trading Corp.","Chittick Fire & Security Corporation","Chomar Industrial / DMCI","Constructive Industrial Equipment Trading","CV Janitorial Maintenance and Utility","Ecolab Philippines","Eunicarl Industrial Sales Corporation","Express Waste Solutions, Inc.","FEMC International Philippines, Inc.","First Choice Industrial Safety Corporation","Flagship Petroleum Carriers Inc.","Fueltek Innovations Inc.","Germand Marketing & Industrial Corporation","Glenda De Guzman","Greenpower Technology Services Inc.","Harmonic","HOA's General Construction","Inspecta Inspection Services Corporation","JITS Corporation","Kapit Bisig Ugnayan Multi-Purpose Cooperative","King's Rubber International. Inc.","Kyuten Enterprises","Lemery Oil Terminal Corporation","LJ Resources Company","Lufthansa Technik Philippines, Inc.","Megatechnip Incorporated","MNS Builders","MTEC Water Treatment Technologies. Inc.","Nagano Services Corporation","New Blue Framework Construction Inc.","Oleo-Fats, Inc.","Olympic Engineering and Sales Corporation","On Semiconductor Philippines Corporation","Parsh Marine Services Philippines Inc.","Phil Gold Processing & Refining Corporation","PMSI","Ramy Construction Inc.","Refletech Engineering Services","Roentgen Separation","Sakamoto Orient Chemical Corp.","Savior Construction","Siegwerk","Smart Biz Trading","Soil Philippines Index Testing, Inc.","Taisei - DMCI Joint Venture","Three Edz Sandblasting Services","Upholder General Construction & Supply","Victoria's Milling","World-Tech Marketing Corporation","X'former Inc.","A Blackstone Energy Corporation","AP Renewables Inc.","C.M. Pancho Construction, Inc.","CBRE GWS IFM Philippines Corporation","Cifra Industrial Services Corporation","Cimech Systems Industries, Inc.","Cutech Process Services Philippines Inc.","D.D. Engineering Services","Da-Ri Trenchless Technologies International Corp.","DNV AS Philippine Branch","Ernesto","Esguerra Shipping Corporation","Fabcon Philippines Inc.","First NatGas Power Corporatoon","FTPontillo Builders and Engineering Services","Henkel Philippines Inc.","Hydron Corporation","JD Dimalanta Construction Services","KCD Builders Corporation","Konstrutek Builders and Technical Services, Inc.","Lloyd's Register Asia","Maigue Engineering & Construction Services","Measurement and Control Technology, Inc.","Mecon Systems Services & Maintenance Productions","MHI Power ( Philippines ) Plant Services Corp.","MOOG Controls Corporation - Philippine Branch","Morse Hydraulic System Corporation","MRN Techniques & Services","NC Lanting Security Specialist Agency","NPG Worlwide Construction Materials Wholesaling","PBI","Petron Corporation","Powerflo Solution Asia Pty. Ltd.","Presam Construction and General Services Inc.","Prime Metro BMD Corporation","Pristine Energy Transfer Corporation","RC Avila Construction & Engineering Supplies","Reliability & Integrity Management","Renato Sagarino","Samsung CNT Philippine Corporation","San Roque Power Corporation","SGS Philippines Inc.","Shimizu Philippine Contractors, Inc.","Siemens Power Operations Inc.","Southern J Power Electric, Inc.","Swordfish Marine Services Corporation","Tai & Chyun Philippines Inc.","VR Vargas Builders","Watercorp Technologies","Abda Construction Inc.","Desco Incorporated","Metrowide Commodities Corporation","OSA Industries Philippines Inc.","Peamkonstrukt Builders & Supply","QAQ Industrial Instrumentation & Controls","Tradetech Marketing","Trans World Trading Co., Incorporated","Adam Kennedy Industrial Solutions","Aichemy Industrial Controls Engineering Services","Argentech, Incorporated","Brenton Engineered Products Corporation","BTSMC Managing Solution Inc.","Chemrez Technologies Inc.","CIGC Corporation","Energized Industrial Corporation","Filblast Industrial Trading Corporation","JAV Thermal Solutions, Inc.","Josefa Slipways, Inc.","King's Safetynet Inc.","Metalite Builders Development Co., Inc.","MJL Industrial Engineering Services","Safeseas Shipping Agency","SAS Builders Corporation","Therma Luzon Inc.","Trescore Industrial Solutiom Corporation","Ecology Transport Marine Specialist","Kunimori Engineering Works- Manila","RTM Industrial Solutions Inc.","Sparta Construction Corporation","Subic Drydock Corporation","Subsea Services Incorporated","Taihei Alltech Construction ( Phils. ) Inc.","Zamora Use","CPM Construction & Gen. Services Inc.","D.M. Consunji, Inc.","E E I Corporation","JX Nippon Philippines Inc.","Rhajtek Industrial Systems and Construction Inc.","SFCS Corporation","Vispet Development Corporation","Sta. Clara International Corporation","Amspec Testing Services Pte. Ltd.","Fleet Maintenance and Consultancy Services","Scientifc Driiling Inc.","Yuhantech Philippines, Inc.","Boskalis Philippines Inc.","CJI General Services Inc.","Filoil Logistics Corporation","Moreta Shipping Lines Inc.","The Fourth Dimension Inc.","Travis General Services Inc.","United Perlite Corporation","Anden Construction Company Inc.","Mitsubishi Power (Philippines) Inc.","Shell Pilipinas Corporation","LSI, Inc.","Aboitiz Construction Inc.","I & E Industrial Systems Services","Nalco Philippines Inc.","Flagship Petroleum Carriers, Inc.","Technology Exports Services Corp.","UTV Construction & Engineering Services","Nippon Kaiji Kyokai","EI Construction and Development Corporation","Taganito HPAL Nickel Corporation","Seatrium Subic Shipyard, Inc.","Joel Chavez Construction","Maibarara Geothermal Inc.","Coral Bay Nickel Corporation","Linde Philippines Inc.","Bureau Veritas S.A.","JGC Philippines Inc.","LS Instrumentation Sales & Services","Nikkeru Plant Maintenance","Mechatrends Contractors Corporation","Industrial Controls Systems, Inc.","Linseed Field Corporation","Herma Shipping and Transport Corporation","Sales record not found"
];

// Get all from database
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records') AND company_name IS NOT NULL AND company_name != ''");
$db_companies = [];
while ($row = $result->fetch_assoc()) {
    $db_companies[] = $row['company_name'];
}

// Find extras in database not in authoritative list
$auth_lower = array_map('strtolower', $authoritative);
$extra_in_db = [];
foreach ($db_companies as $company) {
    if (!in_array(strtolower($company), $auth_lower)) {
        $extra_in_db[] = $company;
    }
}

echo "Companies in DATABASE but NOT in your AUTHORITATIVE list:\n";
if (count($extra_in_db) > 0) {
    foreach ($extra_in_db as $company) {
        echo "  - $company\n";
    }
} else {
    echo "  (None - all database companies are in your list)\n";
}

echo "\nDatabase total: " . count($db_companies) . "\n";
echo "Authoritative total: " . count($authoritative) . "\n";
echo "Match: " . (count($db_companies) === count($authoritative) ? "✓" : "✗") . "\n";
?>
