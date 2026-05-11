<?php
require 'db_config.php';

// Get ALL unique company names (without the exclusion filter)
$sql = "SELECT DISTINCT company_name, COUNT(*) as cnt
        FROM delivery_records
        WHERE company_name IS NOT NULL AND company_name != ''
        GROUP BY company_name
        ORDER BY company_name";

$result = $conn->query($sql);
$all_companies = [];
while ($row = $result->fetch_assoc()) {
    $all_companies[$row['company_name']] = $row['cnt'];
}

echo "Total unique company names in database (no filters): " . count($all_companies) . "\n\n";

// Show excluded companies
$excluded = ['Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila'];
echo "Excluded entries (in DB but filtered out):\n";
foreach ($excluded as $name) {
    if (isset($all_companies[$name])) {
        echo "- '$name' (Count: " . $all_companies[$name] . ")\n";
    }
}

// Count valid client companies
$valid_count = 0;
$valid = [];
foreach ($all_companies as $name => $cnt) {
    if (!in_array($name, $excluded)) {
        $valid_count++;
        $valid[$name] = $cnt;
    }
}

echo "\n\nValid client companies (excluding above): " . $valid_count . "\n";

// Now check which ones from your provided list are missing
$provided = [
    "Andison Manila Use", "to Andison Manila", "5 Axis International Corporation",
    "Abesco Construction Development Corp.", "ACAA Inc.", 
    "AME Construction & Gen. Engineering Services", "Archonell Inc.",
    "Argotek Innovative Exchange, Inc.", "Ascof Construction. Inc.",
    "Assistco Energy & Industrial Corporation", "Bolintaguen Cons and Trading Corp.",
    "Chittick Fire & Security Corporation", "Chomar Industrial / DMCI",
    "Constructive Industrial Equipment Trading", "CV Janitorial Maintenance and Utility",
    "Ecolab Philippines", "Eunicarl Industrial Sales Corporation",
    "Express Waste Solutions, Inc.", "FEMC International Philippines, Inc.",
    "First Choice Industrial Safety Corporation", "Flagship Petroleum Carriers Inc.",
    "Fueltek Innovations Inc.", "Germand Marketing & Industrial Corporation",
    "Glenda De Guzman", "Greenpower Technology Services Inc.",
    "Harmonic", "HOA's General Construction",
    "Inspecta Inspection Services Corporation", "JITS Corporation",
    "Kapit Bisig Ugnayan Multi-Purpose Cooperative", "King's Rubber International. Inc.",
    "Kyuten Enterprises", "Lemery Oil Terminal Corporation",
    "LJ Resources Company", "Lufthansa Technik Philippines, Inc.",
    "Megatechnip Incorporated", "MNS Builders",
    "MTEC Water Treatment Technologies. Inc.", "Nagano Services Corporation",
    "New Blue Framework Construction Inc.", "Oleo-Fats, Inc.",
    "Olympic Engineering and Sales Corporation", "On Semiconductor Philippines Corporation",
    "Parsh Marine Services Philippines Inc.", "Phil Gold Processing & Refining Corporation",
    "PMSI", "Ramy Construction Inc.",
    "Refletech Engineering Services", "Roentgen Separation",
    "Sakamoto Orient Chemical Corp.", "Savior Construction",
    "Siegwerk", "Smart Biz Trading",
    "Soil Philippines Index Testing, Inc.", "Taisei - DMCI Joint Venture",
    "Three Edz Sandblasting Services", "Upholder General Construction & Supply",
    "Victoria's Milling", "World-Tech Marketing Corporation",
    "X'former Inc.", "Zamora display"
];

$missing_from_db = [];
foreach ($provided as $company) {
    if ($company && $company !== '' && !in_array($company, ['(Blanks)', 'Sold To', 'Warranty Replacement', 'Sales record not found', 'replaced to MCXL-XWHM  SN : KA425-0013916', 'replaced to Ultra Unit SN : 5220ULT01242400142', 'replaced to XT-XWHM  SN MA225-006997'])) {
        if (!isset($all_companies[$company])) {
            $missing_from_db[] = $company;
        }
    }
}

if (!empty($missing_from_db)) {
    echo "\n\nCompanies from your list NOT in database (" . count($missing_from_db) . "):\n";
    foreach ($missing_from_db as $company) {
        echo "- $company\n";
    }
}
?>
