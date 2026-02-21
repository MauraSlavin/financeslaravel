<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Monthly;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;
use DateTime;



use Carbon\Carbon;

class TransactionsController extends Controller
{

    // page with "Transactions Controller (test).
    public function test() {
        echo "<br>Transactions Controller (test).";
    }


    // beginDate defaults to a month ago
    // end date defaults to today
    public function setDefaultBeginEndDates($beginDate = null, $endDate = null) {
        
        // end date defaults to today
        if($endDate == null) {
            $endDate = date("Y-m-d");
        }

        // beginDate defaults to a month ago
        if($beginDate == null) {
            $beginDate = date('Y-m-d', strtotime('-45 day'));
        }

        return [$beginDate, $endDate];
    }


    // get transactions from csv file
    public function readUploadCsv($accountName) {

        // csv files are expected to be in the public/uploadFiles folder.
        $fullFilePath = public_path('uploadFiles/' . strtolower($accountName) . '.csv');

        // does the csv file exist?
        if (!file_exists($fullFilePath)) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' not found'], 404);
        }

        // open the file
        $handle = fopen($fullFilePath, 'r');

        // ignore first 3 lines of VISA csv file
        if($accountName == 'VISA') {
            for($i = 1; $i <= 3; $i++) $garbage = fgetcsv($handle, 1000, ',');
        }

        // get header row
        $headers = fgetcsv($handle, 1000, ',');

        // make sure headers exist
        if($headers === FALSE) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' has no headers'], 411);
        }

        // init transactions array
        $newCsvData = [];

        // read each record
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rowNumber++;
            foreach($headers as $hdrIdx=>$header) {
                $newCsvData[$rowNumber][$header] = $row[$hdrIdx];
            }
        }

        // close the csv file
        fclose($handle);

        return $newCsvData;
    }   // end function readUploadCsv


    // get tolls from csv file
    public function readTollCsv() {

        // removes duplicates from tollData (no dup rcds from CSV file)
        function removeDupCSVTolls($tollData) {

            // toll data with duplicates removed
            $noDupsTollData = [];
            
            // for each toll records, only write it to the new noDupsTollData if it is not already there.
            foreach($tollData as $key=>$tollRecord) {

                // if $row is a duplicate, don't add it to tollData
                if(isUniqueCSVToll($tollRecord, $noDupsTollData)) {
                    $noDupsTollData[] = $tollRecord;
                } else {
                    error_log("This is a duplicate entry in the tolls CSV file, so it was not written to the tolls table:");
                    error_log(json_encode($tollRecord));
                    error_log("\n\n");
                }
            }

            return $noDupsTollData;
        }   // end of function removeDupCSVTolls

        // returns true if tollRecord is not already in noDupsTollData
        function isUniqueCSVToll($tollRecord, $noDupsTollData) {

            $isUnique = true;  // assume unique unless found otherwise
            foreach($noDupsTollData as $key=>$currToll) {
                if($currToll == $tollRecord) {
                    return false;   // it's already in the CSV file
                }
            }
            return $isUnique;   // Unique if no dups found
        }   // end of isUniqueCSVToll

        // returns true if tollRecord is not already in $tableTolls (from tolls table)
        function isUniqueTableToll($tollRecord, $tableTolls) {

            $isUnique = true;  // assume unique unless found otherwise
            foreach($tableTolls as $key=>$currToll) {
                // if date, time, transponder & amount match, it's a duplicate
                if(
                    $currToll->date == $tollRecord["Transaction Date"] &&
                    $currToll->time == $tollRecord["Transaction Time"] &&
                    substr($currToll->transponder, -8) == substr($tollRecord["Transponder/Plate"], -8) &&
                    $currToll->amount == $tollRecord["Outgoing"]
                ) {
                    return false;   // it's already in the tolls table
                }
            }

            return $isUnique;   // Unique if no dups found
        }   // end of isUniqueTableToll
        

        // removes duplicates from tollData that are already in the tolls table
        // considered a dupe if the car, date, time and amt match
        function removeDupTableTolls($tollData) {

            // need tolls from tolls table to compare.  Only need those in the date range in $tollData
            [$minDate, $maxDate] = getTollsDateRange($tollData);

            // get tolls from table
            $tableTolls = DB::table('tolls')
                ->select(
                    DB::raw('`Transaction Date` as date'),
                    DB::raw('`Transaction Time` as time'),
                    DB::raw('`Transponder/Plate` as transponder'),
                    DB::raw('`Outgoing` as amount')
                )
                ->whereNull('deleted_at')
                ->where(DB::raw('`Transaction Date`'), '>=', $minDate)
                ->where(DB::raw('`Transaction Date`'), '<=', $maxDate)
                // ->where('`Transaction Date`', '>=', $minDate)
                ->get()->toArray();

            // toll data with duplicates removed
            $noDupsTollData = [];

            // for each toll records, only write it to the new noDupsTollData if it is not in the tolls table ($tableTolls)
            foreach($tollData as $key=>$tollRecord) {

                // if $row is a duplicate, don't add it to tollData
                if(isUniqueTableToll($tollRecord, $tableTolls)) {
                    $noDupsTollData[] = $tollRecord;
                } else {
                    error_log("This is a duplicate entry in the tolls TABLE, so it was not written to the tolls table:");
                    error_log(json_encode($tollRecord));
                    error_log("\n\n");
                }
            }

            return $noDupsTollData;
        }   // end of function removeDupTableTolls
        

        // get min and max dates in tollData
        function getTollsDateRange($tollData) {

            // init min and max dates
            $minDate = $tollData[0]["Transaction Date"];
            $maxDate = $tollData[0]["Transaction Date"];

            // change min and max dates to the actual min and max dates in tollData
            foreach($tollData as $tollRecord) {
                if($tollRecord["Transaction Date"] < $minDate) $minDate = $tollRecord["Transaction Date"];
                if($tollRecord["Transaction Date"] > $maxDate) $maxDate = $tollRecord["Transaction Date"];
            }

            return [$minDate, $maxDate];
        }   // end of function getTollsDateRange

        // csv files are expected to be in the public/uploadFiles folder.
        $fullFilePath = public_path('uploadFiles/tolls.csv');

        // does the csv file exist?
        if (!file_exists($fullFilePath)) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' not found'], 404);
        }

        // open the file
        $handle = fopen($fullFilePath, 'r');

        // get header row
        $headers = fgetcsv($handle, 1000, ',');

        // make sure headers exist
        if($headers === FALSE) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' has no headers'], 411);
        }

        // init transactions array
        $tollData = [];

        // read each record
        // keep these values for tolls table
        $dataHdrsToKeep = [
            "Agency",
            "Transponder/Plate",
            "Transaction Date",
            "Transaction Time",
            "Entry Plaza",
            "Exit Plaza",
            "Exit Lane",
            "Outgoing",
            "Car",
            "Trip"
        ];

        // build tollData array
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rowNumber++;
            foreach($headers as $hdrIdx=>$header) {
                if(in_array($header, $dataHdrsToKeep)) {
                    $tollData[$rowNumber][$header] = $row[$hdrIdx];
                }
            }
        }

        // close the csv file
        fclose($handle);

        // eliminate dups in csv file
        $tollData = removeDupCSVTolls($tollData);
        
        // eliminate tolls from tollData that are already in tolls table
        $tollData = removeDupTableTolls($tollData);

        return $tollData;
    }   // end function readTollCSV

    
    // tweak records for Checking to make import more helpful
    public function modifyCsvForChecking($newCsvData) {

        foreach($newCsvData as $idx=>$record) {
        
            // Combine "Amount Debit" or "Amount Credit" into Amount (won't be a number in both)
            if($record["Amount Debit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Debit"];
            else if ($record["Amount Credit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Credit"];
            else $newCsvData[$idx]["Amount"] = "0"; // to prevent an error

            // Amount Credit and Amount Debit are no longer needed
            unset($newCsvData[$idx]["Amount Credit"], $newCsvData[$idx]["Amount Debit"]);
        }

        return $newCsvData;
    }


    // tweak records for VISA to make import more helpful
    public function modifyCsvForVISA($newCsvData) {

        foreach($newCsvData as $idx=>$record) {

            // Combine "Amount Debit" or "Amount Credit" into Amount (won't be a number in both)
            if($record["Amount Debit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Debit"];
            else if ($record["Amount Credit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Credit"];
            else $newCsvData[$idx]["Amount"] = "0"; // to prevent an error

            // Amount Credit and Amount Debit are no longer needed
            unset($newCsvData[$idx]["Amount Credit"], $newCsvData[$idx]["Amount Debit"]);
        }

        return $newCsvData;
    }


    // Convert csv data to transaction records
    public function convertCsv($newCsvData, $accounts, $accountName, $key_dummies, $max_splits) {

        // handle each record for the specific account

        // need fields in the transactions table
        $transaction_fields = json_decode(file_get_contents(public_path('uploadFiles/transactionsFields.json')));

        // init newRecords
        $newRecords = [];

        // will need date info
        $dayOfMonth = date("d");
        $month = date("m");
        $year = date("Y");

        // get the day of the month for the last transaction on a statement
        //      defaults to the last day of the month
        $lastStmtDay = null;
        foreach ($accounts as $i=>$thisAccount) {
            if ($thisAccount->accountName == $accountName) {
                $lastStmtDay = $thisAccount->lastStmtDate;
                break;
            }
        }

        // get the last day of the month, if no lastStmtDate in accounts table
        if($lastStmtDay == null) {
            if(in_array($month, ['01', '03', '05', '07', '08', '10', '12'])) {
                $lastStmtDay = '31';
            } else if($month == '02') {
                $lastStmtDay = '29';
            } else {
                $lastStmtDay = '30';
            }
        }

        // get prefixes of toFrom's from banks to ignore
        $ignore = DB::table("tofromaliases")
            ->whereNull('deleted_at')
            ->where('transToFrom', 'IGNORE')
            ->pluck('origToFrom');
                    
        // get mapping information (which csv fields map to which trans fields, including formulas)
        $mapping = DB::table("accounts")
            ->leftJoin("uploadmatch", "accounts.id", '=', "uploadmatch.account_id")
            ->select("csvField", "transField", "formulas")
            ->whereNull('accounts.deleted_at')
            ->whereNull('uploadmatch.deleted_at')
            ->where("accountName", $accountName)
            ->get()->toArray();
        
        $current_split_idx = 0;

        // build transaction record from csv file record and write to the transactions table
        foreach($newCsvData as $i=>$transaction) {

            $fieldsLeft = [];   // transaction fields that have not yet been calculated
            $newRecord = new \stdClass();    // start with a fresh record to write to the db
            $splitRecords = []; // additional records for splits

            // all the fields are left, to begin with
            foreach($transaction_fields as $field) $fieldsLeft[] = $field;

            // set account
            $newRecord->account = $accountName;
            // account is done, remove from fieldsLeft
            removeElementByValue($fieldsLeft, 'account');

            // set fields as dictated by mapping (in uploadmatch table)
            foreach($mapping as $idx=>$map) {

                // if formula is null, it's a straight assignment
                if($map->formulas == null) {
                    $newRecord->{$map->transField} = $transaction[$map->csvField];
                    // field is done, remove from fieldsLeft
                    removeElementByValue($fieldsLeft, $map->transField);
                } else {
                    // if formula has a value, massage and evaluate it
                    //      the csvField indicates which element of the $transactions record,
                    //      so replace the csvField with the array reference
                    // see README.MD for allowed formulas
                    $formula = $map->formulas;  // string to be changed
                    $search = $map->csvField;   // string to look for
                    $replace = "\$transaction['".($map->csvField)."']"; // new string to replace
                    $formula = str_replace($map->csvField, $replace, $formula);

                    // handle concatenated fields
                    $isConcatPos = strpos($formula, ' + ');
                    if($isConcatPos !== false) {
                        $string2 = trim(substr($formula, $isConcatPos+2));
                        $replaceString2 = "\$transaction['".($string2)."']";
                        $formula = str_replace($string2, $replaceString2, $formula);
                        $formula = str_replace(" + ", " . ' ' . ", $formula);
                    }

                    // evaluate the formula to get the data needed
                    $newRecord->{$map->transField} = eval( "return $formula;");

                    // field is done, remove from fieldsLeft
                    removeElementByValue($fieldsLeft, $map->transField);
                }
                
                // the trans_date and clear_date need to be reformatted (mm/dd/yyyy to yyyy-mm-dd)
                if(strpos($map->transField, "_date") !== false) {
                     $newRecord->{$map->transField} = reformatDate( $newRecord->{$map->transField});
                }
            }

            // guess at statement date.  
            //  -- If it clears before the lastStmtDay, assume it's in the current month's statement
            //  -- Otherwise, make the statement date the next month.
            if($newRecord->clear_date <= ($year . "-" . $month . "-" . $lastStmtDay)) {
                $newRecord->stmtDate = date("y") . "-" . date("M");
            } else {
                // if Dec, add 1 to year when adding one to the month
                if($month == 12) {
                    $stmtDateYear = date("y") + 1;
                } else {
                    $stmtDateYear = date("y");
                }
                $newRecord->stmtDate = $stmtDateYear . "-" . date("M", mktime(0,0,0,$month+1,$dayOfMonth,$year));
            }        

            // stmtDate is done, remove from fieldsLeft
            removeElementByValue($fieldsLeft, "stmtDate");

            // remove strings to ignore from transaction toFrom
            $lc_toFrom = strtolower($newRecord->toFrom);
            foreach ($ignore as $ignoreString) {
                $lc_toFrom = preg_replace('/\b' . preg_quote(strtolower($ignoreString), '/') . '\b/', '', $lc_toFrom);
                $lc_toFrom = trim($lc_toFrom);
            }
            $newRecord->toFrom = $lc_toFrom;

            // handle different length origToFrom values
            $toFrom = $newRecord->toFrom;
            $toFromAlias = DB::table("tofromaliases")
                ->whereNull('deleted_at')
                ->where('origToFrom', '=', DB::raw('LEFT(?, LENGTH(origToFrom))'))
                ->setBindings([$toFrom])
                // ->dumpRawSql()
                ->first();

            // handle default categories, notes, tracking, and splits
            if($toFromAlias) {
                $newRecord->toFrom = $toFromAlias->transToFrom;  // might not have been caught before; if origToFrom in table < 11 chars
                $category = $toFromAlias->category;
                $newRecord->category = $category ?? null;
                
                // handle extraDefaults
                $extraDefaults = $toFromAlias->extraDefaults;

                if($extraDefaults) {
                    $extraDefaultsArray = json_decode($extraDefaults, true);

                    // Handle notes
                    if (isset($extraDefaultsArray['notes'])) {
                        $newRecord->notes = $extraDefaultsArray['notes'];
                    }
                    
                    // Handle tracking
                    //  -- just beginning - handle multiple tracking (one for each split)
                    $multipleTracking = false;
                    // is there default tracking?
                    if (isset($extraDefaultsArray['tracking'])) {
                        // if it's an array, there may be more than one tracking element
                        if(is_array($extraDefaultsArray['tracking'])) {
                            // use the first tracking in the first new record
                            $newRecord->tracking = $extraDefaultsArray['tracking'][0];
                            // if the array has more than one element, store the extra tracking elements in $multipleTracking
                            //      to write to cloned split transaction records.
                            if(count($extraDefaultsArray['tracking']) > 1) {
                                $multipleTracking = array_slice($extraDefaultsArray['tracking'], 1);
                            }

                        // if tracking is just text, that will be the tracking for the original and any split transactions.
                        } else {
                            // multipleTracking will be false in this case
                            $newRecord->tracking = $extraDefaultsArray['tracking'];
                        }
                    }

                    // Handle splits
                    if (isset($extraDefaultsArray['splits'])) {

                        if(is_numeric($extraDefaultsArray['splits'])) {
                            $typeSplit = "number";
                            $numberSplits = $extraDefaultsArray['splits'] + 1;      // +1 for original record
                        } else {
                            $typeSplit = "categories";
                            $numberSplits = count($extraDefaultsArray['splits']) + 1;      // +1 for original record
                        }
                        
                        // adjust newRecord
                        $origAmount = $newRecord->amount;
                        $splitAmount = round($origAmount/$numberSplits, 4); // to 4 decimal places; fix manually if needed
                        $newRecord->amount = $splitAmount;
                        $newRecord->total_amt = $origAmount;
                        $newRecord->total_key = $key_dummies[$current_split_idx];
                        $current_split_idx++;
                        if($current_split_idx >= $max_splits) { 
                            error_log("\n\n\n  TOO MANY SPLIT TRANSACTIONS!!  ");
                        }
                        if($newRecord->category == "MikeSpending") {
                            $newRecord->amtMike = $splitAmount;
                            $newRecord->amtMaura = 0;
                        } else if($newRecord->category == "MauraSpending") {
                            $newRecord->amtMaura = $splitAmount;
                            $newRecord->amtMike = 0;
                        } else {
                            $newRecord->amtMaura = round($splitAmount/2, 4);
                            $newRecord->amtMike = round($splitAmount/2, 4);
                        }

                        // create splits from copy of newRecord
                        for( $numSplit = 0; $numSplit < $numberSplits-1; $numSplit++) {

                            // make a full copy
                            $splitRecord = unserialize(serialize($newRecord));

                            // modify as needed
                            if($typeSplit == "categories") $splitRecord->category = $extraDefaultsArray['splits'][$numSplit];
                            else $splitRecord->category = null;

                            if($splitRecord->category == "MikeSpending") {
                                $splitRecord->amtMike = $splitAmount;
                                $splitRecord->amtMaura = 0;
                            } else if($splitRecord->category == "MauraSpending") {
                                $splitRecord->amtMaura = $splitAmount;
                                $splitRecord->amtMike = 0;
                            } else {
                                $splitRecord->amtMaura = round($splitAmount/2, 4);
                                $splitRecord->amtMike = round($splitAmount/2, 4);
                            }

                            // change default tracking, if there were multiple ones
                            if($multipleTracking && isset($multipleTracking[$numSplit])) {
                                $splitRecord->tracking = $multipleTracking[$numSplit];
                            }

                            // append to array of split records
                            $splitRecords[] = $splitRecord;
                        }
                    }

                }
            }

            // add this newRecord to newRecords
            $newRecords[] = $newRecord;
            // add each splitRecord to newRecords
            foreach($splitRecords as $splitRecord) {
                $newRecords[] = $splitRecord;
            }
        }

        return $newRecords;
    }   // end function convertCsv


    // find and question possible duplicates
    // Possible duplicates have:
    //      same account
    //      same toFrom (To Do: check aliases)
    //      trans_date within 2 days
    //      amount in new transaction matches amount in existing transaction
    //              or total_amt in existing transaction
    public function findDuplicates($newRecords) {

        foreach($newRecords as $rcdIdx=>$record) {

            // check dates within two days of trans_date
            $trans_date = Carbon::parse($record->trans_date);
            $orig_date = $trans_date->format('Y-m-d');
            
            // get one & 2 days before trans_date
            $previousDay = $trans_date->subDays(1)->format('Y-m-d');
            // subract another day
            $twoDaysBefore = $trans_date->subDays(1)->format('Y-m-d');
            
            // get one and 2 days after trans_date
            // add 3 to twoDaysBefore
            $nextDay = $trans_date->addDays(3)->format('Y-m-d');
            // add one more day
            $twoDaysAfter = $trans_date->addDays(1)->format('Y-m-d');
            
            // original trans_date is two days before "twoDaysAfter"
            $trans = $trans_date->addDays(1)->format('Y-m-d');

            // formatted dates
            $formattedDates = [
                $twoDaysBefore,
                $previousDay,
                $orig_date,
                $nextDay,
                $twoDaysAfter
            ];

            $dupsMaybe = DB::table('transactions')
                ->whereNull('deleted_at')
                ->where('account', $record->account)
                ->where('toFrom', $record->toFrom )
                ->whereIn('trans_date', $formattedDates)
                ->where(function ($query) use ($record) {
                    $query->where('amount', $record->amount)
                        ->orWhere('total_amt', $record->amount);
                })
                ->get()->toArray();
                           
            // add element to indicate if this might be a duplicate transaction
            // if(count($dupsMaybe) > 0) $newRecords[$rcdIdx]['dupMaybe'] = true;
            if(count($dupsMaybe) > 0) $newRecords[$rcdIdx]->dupMaybe = true;
            // else $newRecords[$rcdIdx]['dupMaybe'] = false;
            else $newRecords[$rcdIdx]->dupMaybe = false;
            
        }
        
        return $newRecords;
    }


    // write transactions to database; adds ids to $records
    public function writeNewRecordsToTransactions($records) {

        $recordsWithIds = [];  // ids assigned when record written

        foreach($records as $record) {
            // write the transaction to the database.
            try {
                $dupMaybe = $record->dupMaybe ?? false;
                $split_total = $record->split_total ?? null;
                unset($record->dupMaybe);
                unset($record->split_total);

                $result = DB::table('transactions')->insertGetId((array)$record);
                $record->id = $result;

                $record->dupMaybe = $dupMaybe;
                $record->split_total = $split_total;
                $recordsWithIds[] = $record;     // for the blade to show uploaded transactions with id

            } catch (\Exception $e) {
                \Log::error('Error adding record to the transactions table: ' . $e->getMessage() . "\nRecord: " . json_encode($record));
                throw $e;
            }
        }

        return $recordsWithIds;
    }


    // get budget for the full year for each category and year
    function findTotalBudget($budgets, $year, $category) {
        foreach ($budgets as $budget) {
            if ($budget->year == $year && $budget->category == $category) {
                return floatval($budget->total_budget);
            }
        }
        
        // If no match is found, return 0
        return 0;
    }


    // get total spent for each category
    function findSpent($spentTotals, $category) {
        foreach ($spentTotals as $spentTotal) {
            if ($spentTotal->category == $category) {
                return floatval($spentTotal->spent);
            }
        }
        
        // If no match is found, return 0
        return 0;
    }


    // return msg if remote and local databases don't appear to be in sync
    //  look to see if copied field in any table is not 'new'
    public function checkDbDiscrep() {

        $outOfSyncTables = [];
        $outOfSyncAccounts = [];
        $msg = '';

        // delete "inpt" fields from retirementdata, first
        $response = DB::table('retirementdata')
            ->where('type', 'inpt')
            ->delete();
        error_log("Deleted from REMOTE retirementdata table: " . json_encode($response));
        $response = DB::connection('mysqllocal')
            ->table('retirementdata')
            ->where('type', 'inpt')
            ->delete();
        error_log("Deleted from LOCAL retirementdata table: " . json_encode($response));

        $tables = [
            'accounts',
            'bucketgoals',
            'budget',
            'carcostdetails',
            'categories',
            'monthlies',
            'notes',
            'retirementdata',
            'tofromaliases',
            'tolls',
            'transactions',
            'trips',
            'uploadmatch'
        ];

        // check for TABLES out of sync
        // if copied field is not new, it's out of sync
        foreach($tables as $table) {
            // error_log("account: " . $table);
            $uncopiedRemoteRcds = DB::table($table)
                ->whereNot('copied', 'yes')
                ->count();
            // error_log("uncopiedRemoteRcds: " . $uncopiedRemoteRcds);

            $uncopiedLocalRcds = DB::connection('mysqllocal')
                ->table($table)
                ->whereNot('copied', 'yes')
                ->count();
            // error_log("uncopiedLocalRcds: " . $uncopiedLocalRcds);

            if($uncopiedRemoteRcds != 0 || $uncopiedLocalRcds != 0) {
                $outOfSyncTables[] = $table;
            }
        }
        // error_log("# outOfSyncTables: " . count($outOfSyncTables));
        // error_log(json_encode($outOfSyncTables));
        // error_log("\n\n");

        // update msg, if needed.
        if(count($outOfSyncTables) > 0) {
            $msg = "Tables out of sync: " . implode(", ", $outOfSyncTables) . ".  ";
        }
        // error_log("msg: " . $msg);

        // Check for ACCOUNT balances out of sync.
        $accounts = DB::table('accounts')
            ->whereNull('deleted_at')
            ->pluck('accountName');

        // check balances by account
        foreach($accounts as $account) {
            error_log("------------- account: " . $account);
            $balanceRemote = DB::table('transactions')
                ->where('account', $account)
                ->whereNull('deleted_at')
                ->sum('amount');
            error_log("balanceRemote: " . $balanceRemote);
            
            $copiedRemote = DB::table('transactions')
                ->where('account', $account)
                ->whereNot('copied', 'yes')
                ->get()->toArray();
            error_log("copiedRemote:  " . json_encode($copiedRemote));

            $balanceLocal = DB::connection('mysqllocal')
                ->table('transactions')
                ->where('account', $account)
                ->whereNull('deleted_at')
                ->sum('amount');
            error_log("balanceLocal:  " . $balanceLocal);

            $copiedLocal = DB::connection('mysqllocal')
                ->table('transactions')
                ->where('account', $account)
                ->whereNot('copied', 'yes')
                ->get()->toArray();
            error_log("copiedLocal:   " . json_encode($copiedLocal));

            if($balanceRemote != $balanceLocal
                || count($copiedLocal) > 0
                || count($copiedRemote) > 0
            ) {
                $outOfSyncAccounts[$account] = $account;
            }
        }
        // error_log("-----------------------# outOfSyncAccounts: " . count($outOfSyncAccounts));
        // foreach($outOfSyncAccounts as $acct) error_log(" --- " . $acct);
        // error_log("\n\n");

        // update msg, if needed
        if(count($outOfSyncAccounts) > 0) {
            $msg .= "Accounts out of sync: " . implode(", ", $outOfSyncAccounts) . ".  ";
        }
        // error_log("\nmsg: " . $msg . "\n");

        // return msg to SYNC databases, if discrepencies found.
        return $msg;
    }


    // List of accounts with balances (cleared & register), Last Balanced, and button to see transactions for that account
    //      includes line "all" for all transactions
    public function index($acctsMsg = null) {
        
        // Show alert if mismatch between databases found when connected to remote database
        if(App::environment('remote')) $acctsMsg .= $this->checkDbDiscrep();

        // get all account names & ids
        $results = DB::table('accounts')
            ->select("id", "accountName")
            ->whereNull('deleted_at')
            ->where("type", "trans")
            ->get()->toArray();
        $accountNames = array_column($results, 'accountName');
        $accountIds = array_column($results, 'id');

        $accounts = DB::table('transactions')
            ->select(
                'account',
                DB::raw('SUM(CASE WHEN clear_date IS NOT NULL THEN amount ELSE 0 END) AS cleared'),
                DB::raw('SUM(amount) AS register'),
                DB::raw('MAX(lastBalanced) AS max_last_balanced')
            )
            ->whereNull('deleted_at')
            ->whereIn(
                'account',
                $accountNames
            )
            ->groupBy('account')
            ->get()
            ->toArray();

        // order accounts alphabetically
        usort($accounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        $all = new \stdClass();
        $all->account = "all";
        $all->cleared = 0;
        $all->register = 0;
        $all->max_last_balanced = "";
        foreach($accounts as $account) {
            $all->cleared += $account->cleared;
            $all->register += $account->register;
            $all->max_last_balanced = max($all->max_last_balanced, $account->max_last_balanced);
        }

        // add too array to be written to the page
        $accounts[] = $all;

        // format $ amounts  for each account (& all)
        foreach($accounts as $acctIdx=>$account) {
            $accounts[$acctIdx]->cleared = number_format($account->cleared, 2);
            $accounts[$acctIdx]->register = number_format($account->register, 2);
        }

        return view('accounts', ['accounts' => $accounts, 'acctsMsg' => $acctsMsg]);
    }

    function calcSplitTotals($transactions) {

        // get splits that might not be in transactions & ids
        $splitKeys = array_column($transactions, 'total_keys');
        $ids = array_column($transactions, 'id');

        $extraSplitTransactions = DB::table("transactions")
            ->whereNull('deleted_at')
            ->whereIn("total_key", $splitKeys)
            ->whereNotIn("id", $ids)
            ->get()->toArray();

        if(count($extraSplitTransactions) != 0) {
            error_log("\n\n\nNot ALL splits are in this timeframe!!!\n\n\n");
        }

        // make sure all transactions have a total_key
        foreach($transactions as $trxIdx=>$transaction) {
            if(!isset($transaction->total_key)) $transactions[$trxIdx]->total_key = null;
        }

        // Group transactions by total_key and sum amounts
        if($ids != []) {
            $splitTotals = array_reduce($transactions, function($splitSum, $item) {
                if ($item->total_key) {
                    $splitSum[$item->total_key] = ($splitSum[$item->total_key] ?? 0) + floatval($item->amount);
                }
                return $splitSum;
            }, []);
        } else {
            $splitTotals = [];
        }

        if(count($splitTotals) > 0) {
            foreach($transactions as $transaction) {
                $splitTotal = $splitTotals[$transaction->total_key] ?? null;
                $transaction->split_total = round($splitTotal, 4);
            }
        } else {
            foreach($transactions as $transaction) {
                $transaction->split_total = "n/a";
            }
        }
        
        return $transactions;
    }   // end of function calcSplitTotals


    // for route /accounts/{accountName}/{beginDate}/{endDate}
    public function transactions($accountName, $beginDate = 'null', $endDate = 'null',  $clearedBalance = NULL, $registerBalance = NULL, $lastBalanced = NULL) {

        // begin & end date default to a string null to keep the place in the url
        if($beginDate == 'null') $beginDate = null;
        if($endDate == 'null') $endDate = null;

        // set beginDate and endDate if not passed in (or null)
        if($beginDate == null || $endDate == null) {
            [$beginDate, $endDate] = $this->setDefaultBeginEndDates($beginDate, $endDate);
        }

        // get the accounts information for all accounts
        $accounts = DB::table("accounts")
            ->whereNull('deleted_at')
            ->get()->toArray();
        // error_log("\naccounts: ");
        // foreach($accounts as $thisOne) error_log(" - " . json_encode($thisOne));

        // get all previously used toFrom values
        $toFroms = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("toFrom")->toArray();
        $toFroms = array_column($toFroms, 'toFrom');
        $toFroms = str_replace(" ", "%20", json_encode($toFroms));

        // get tofromaliases (auto converts what the "bank" uses to what's in the database)
        $tofromaliases = DB::table("tofromaliases")
            ->whereNull('deleted_at')
            ->get()->toArray();
        $tofromaliases = str_replace(" ", "%20", json_encode($tofromaliases));
        // error_log("\ntofromaliases:");
        // foreach($tofromaliases as $thisOne) error_log(" - " . json_encode($thisOne));

        // get all the defined account names
        $accountNames = array_column($accounts, 'accountName');
        $accountIds = array_column($accounts, 'id');
        // error_log("accountNames: " . json_encode($accountNames));
        // error_log("accountIds: " . json_encode($accountIds));

        // cut-off dates for a statement period, if not the end of the month
        $allLastStmtDates = array_column($accounts, 'lastStmtDate');

        $lastStmtDates = [];
        foreach($accounts as $accountIdx=>$account) {
            if($allLastStmtDates[$accountIdx] !== null) {
                $lastStmtDates[] = [
                    'accountName' => $accountNames[$accountIdx],
                    'lastStmtDate' => $allLastStmtDates[$accountIdx]
                ];
            }
        }

        // if accountName not in accounts and is not 'all', it's not a valid accountName
        if(!in_array($accountName, $accountNames) && $accountName != 'all') {
            return response()->json(['error' => $accountName . ' is not a defined account'], 412);
        }

        // get all defined categories
        $categories = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("category")->toArray();
        $categories = array_column($categories, 'category');
        $categories = str_replace(" ", "%20", json_encode($categories));

        // get all used tracking values
        $trackings = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("tracking")->toArray();
        $trackings = array_column($trackings, 'tracking');
        $trackings = str_replace(" ", "%20", json_encode($trackings));

        // get all bucket names
        $buckets = DB::table("transactions")
            ->whereNull('deleted_at')
            ->whereNotNull('bucket')
            ->distinct()->get("bucket")->toArray();
        $buckets = array_column($buckets, 'bucket');
        $buckets = str_replace(" ", "%20", json_encode($buckets));

        // get the outstanding transactions that have not cleared yet for the requested account ('all' for all transactions) and time period
        $outstandingTransactions = DB::table('transactions')
            ->when($accountName != 'all', function ($query) use ($accountName) {
                return $query->where('account', $accountName);
            })
            ->whereNull('deleted_at')
            ->whereNull('clear_date')
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')
            ->get()
            ->toArray();

        // get the cleared transactions for the requested account ('all' for all transactions) and time period
        $clearedTransactions = DB::table('transactions')
            ->when($accountName != 'all', function ($query) use ($accountName) {
                return $query->where('account', $accountName);
            })
            ->whereNull('deleted_at')
            ->where('trans_date', '>=', $beginDate)
            ->where('trans_date', '<=', $endDate)
            ->whereNotNull('clear_date')
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')            ->get()
            ->toArray();

        // combine transactions
        $transactions = array_merge($outstandingTransactions, $clearedTransactions);

        // calc split transaction totals
        $transactions = $this->calcSplitTotals($transactions);

        // Get today's date (for next few queries)
        $thisMonth = date('m');
        $thisYear = date('Y');
        // error_log("thisMonth: " . $thisMonth . "; thisYear: " . $thisYear);
        $firstDay = $thisYear . "-01-01";

        // get amount spent for this category this year
        $spentTotals = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as spent')
            ->whereNull('deleted_at')
            ->where('trans_date', '>=', $firstDay)
            ->groupBy('category')
            ->get()
            ->toArray();

        // get year-to-month budgets by category (budget this year up to and including this month)
        // months to use to get YTM budget
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];
        $selectedMonths = array_slice($months, 0, $thisMonth);
        
        $ytmBudgets = DB::table('budget')
            ->selectRaw("year, category, " . implode(' + ', array_map(fn($m) => "COALESCE($m, 0)", $selectedMonths)) . " as total_budget")
            ->whereNull('deleted_at')
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()->toArray();
        // error_log("new var: \n" . json_encode($ytmBudgets));

        // get full year budgets by category
        $yearBudgets = DB::table('budget')
            ->select('year', 'category', 'total as total_budget')
            ->whereNull('deleted_at')
            ->where('year', $thisYear)
            ->groupBy()
            ->get()
            ->toArray();
        // error_log("new yearBudgets: \n" . json_encode($yearBudgets));

        // add ytd spent, budget through current month, full year budget to transactions variable for each transaction
        // and fill in accountId
        foreach($transactions as $transaction) {
            $ytmBudget = $this->findTotalBudget($ytmBudgets, $thisYear, $transaction->category);
            $thisYearBudget = $this->findTotalBudget($yearBudgets, $thisYear, $transaction->category);
            $spent = $this->findSpent($spentTotals, $transaction->category);
            $transaction->ytmBudget = $ytmBudget;
            $transaction->yearBudget = $thisYearBudget;
            $transaction->spent = $spent;

            // fill in accountId
            $accountIdx = array_search($transaction->account, $accountNames);
            $transaction->accountId = $accountIds[$accountIdx];
        }

        // get cars
        $cars = DB::table('carcostdetails')
            ->whereNull('deleted_at')
            ->where('key', 'Purchase')
            ->pluck('car');

        return view('transactions', ['accountName' => $accountName, 'newTransactions' => [], 'transactions' => $transactions, 'beginDate' => $beginDate, 'endDate' => $endDate, 'accountNames' => $accountNames, 'accountIds' => $accountIds, 'lastStmtDates' => $lastStmtDates, 'toFroms' => $toFroms, 'tofromaliases' => $tofromaliases, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => false, 'clearedBalance' => $clearedBalance, 'registerBalance' => $registerBalance, 'lastBalanced' => $lastBalanced, 'cars' => $cars]);
    }


    // for route /accounts/{$accountName}/balances
    // sets the lastBalanced column to now for the given accountName
    public function balances($accountName) {

        // timestamp for now
        $now = time();
        $now = date('Y-m-d H:i:s', $now);

        try {
            $results = DB::table('transactions')
                ->where('account', $accountName)
                ->whereNull('lastBalanced')
                ->whereNotNull('clear_date')
                ->whereNull('deleted_at')
                ->update(['lastBalanced' => $now, 'copied'=>'needupt']);

            // if no records were updated, change the newest lastBalanced to today
            if($results == 0) {
            DB::table('transactions')
                ->where('account', $accountName)
                ->whereNotNull('clear_date')
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->limit(1)
                ->update(['lastBalanced' => $now, 'copied'=>'needupt']);
            }
            return redirect()->route('accounts')->with("acctsMsg", "Last balances updated.");

        } catch (\Exception $e) {
            \Log::error('Error updating lastBalanced columns in transactions table: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating lastBalanced columns'], 500);
        }

    }


    // returns the subarray with the given key,
    // or false if not found
    public function findArray($array, $key) {
        foreach ($array as $subarray) {
            if ($subarray[0] === $key) {
                return $subarray;
            }
        }
        return false;
    }


    // reads the csv file, massages, and writes to transactions table
    public function upload($accountName) {

        // convert mm/dd/yyyy to yyyy-mm-dd
        function reformatDate($date) {
            $dateParts = explode("/", $date);
            return $dateParts[2] . "-" . $dateParts[0] . "-" . $dateParts[1];
        }
        
        // drop element from an array given the value to be dropped
        function removeElementByValue(&$array, $value) {
            $key = array_search($value, $array);
            if ($key !== false) {
                unset($array[$key]);
            }
        }

       
        // set default beginDate and endDate
        [$beginDate, $endDate] = $this->setDefaultBeginEndDates();

        // get the accounts information for all accounts
        $accounts = DB::table("accounts")
            ->whereNull('deleted_at')
            ->get()->toArray();
        // error_log("\naccounts: ");
        // foreach($accounts as $thisOne) error_log(" - " . json_encode($thisOne));
        $accountIds = array_column($accounts, 'id');

        // get all previously used toFrom values
        $toFroms = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("toFrom")->toArray();
        $toFroms = array_column($toFroms, 'toFrom');
        $toFroms = str_replace(" ", "%20", json_encode($toFroms));
        
        // get tofromaliases (auto converts what the "bank" uses to what's in the database)
        $tofromaliases = DB::table("tofromaliases")
            ->whereNull('deleted_at')
            ->get()->toArray();
        $tofromaliases = str_replace(" ", "%20", json_encode($tofromaliases));

        // get all the defined account names
        $accountNames = array_column($accounts, 'accountName');

        // if accountName not in accounts, it's not a valid accountName
        if(!in_array($accountName, $accountNames)) {
            return response()->json(['error' => $accountName . ' is not a defined account'], 412);
        }

        // cut-off dates for a statement period, if not the end of the month
        $allLastStmtDates = array_column($accounts, 'lastStmtDate');

        $lastStmtDates = [];
        foreach($accounts as $accountIdx=>$account) {
            if($allLastStmtDates[$accountIdx] !== null) {
                $lastStmtDates[] = [
                    'accountName' => $accountNames[$accountIdx],
                    'lastStmtDate' => $allLastStmtDates[$accountIdx]
                ];
            }
        }

        // get all defined categories
        $categories = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("category")->toArray();
        $categories = array_column($categories, 'category');
        $categories = str_replace(" ", "%20", json_encode($categories));

        // get all used tracking values
        $trackings = DB::table("transactions")
            ->whereNull('deleted_at')
            ->distinct()->get("tracking")->toArray();
        $trackings = array_column($trackings, 'tracking');
        $trackings = str_replace(" ", "%20", json_encode($trackings));

        // get all bucket names
        $buckets = DB::table("transactions")
            ->whereNotNull('bucket')
            ->whereNull('deleted_at')
            ->distinct()->get("bucket")->toArray();
        $buckets = array_column($buckets, 'bucket');
        $buckets = str_replace(" ", "%20", json_encode($buckets));

        // get recent existing transactions (to visually check for duplicate transactions)
        $transactions = DB::table("transactions")
            ->where("account", $accountName)
            ->where("trans_date", ">=", $beginDate)
            ->where("trans_date", "<=", $endDate)
            ->whereNull('deleted_at')
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')
            ->orderBy("toFrom")
            ->get()
            ->toArray();
    
        // calc split transaction totals
        $transactions = $this->calcSplitTotals($transactions);

        // Get today's date (for next few queries)
        $thisMonth = date('m');
        $thisYear = date('Y');
        $firstDay = $thisYear . "-01-01";

        // get amount spent for this category this year
        $spentTotals = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as spent')
            ->where('trans_date', '>=', $firstDay)
            ->whereNull('deleted_at')
            ->groupBy('category')
            ->get()
            ->toArray();

        // get year-to-month budgets by category (budget this year up to and including this month)

        // months to sum budgets to get YTM budget
                $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];
        $selectedMonths = array_slice($months, 0, $thisMonth);
        
        $ytmBudgets = DB::table('budget')
            ->selectRaw("year, category, " . implode(' + ', array_map(fn($m) => "COALESCE($m, 0)", $selectedMonths)) . " as total_budget")
            ->where('year', $thisYear)
            ->whereNull('deleted_at')
            ->groupBy('year', 'category')
            ->get()->toArray();
        // error_log("new var: \n" . json_encode($ytmBudgets));
    
        // get full year budgets by category
        $yearBudgets = DB::table('budget')
            ->select('year', 'category', 'total as total_budget')
            ->where('year', $thisYear)
            ->whereNull('deleted_at')
            ->groupBy()
            ->get()
            ->toArray();
        // error_log("new yearBudgets: \n" . json_encode($yearBudgets));

        // add ytd spent, budget through current month, full year budget to transactions variable for each transaction
        foreach($transactions as $transaction) {
            $ytmBudget = $this->findTotalBudget($ytmBudgets, $thisYear, $transaction->category);
            $thisYearBudget = $this->findTotalBudget($yearBudgets, $thisYear, $transaction->category);
            $spent = $this->findSpent($spentTotals, $transaction->category);
            $transaction->ytmBudget = $ytmBudget;
            $transaction->yearBudget = $thisYearBudget;
            $transaction->spent = $spent;
        }

        // READ transactions from csv file
        $newCsvData = $this->readUploadCsv($accountName);
      
        // If Checking, modify csv for upload
        if($accountName == "Checking") $newCsvData = $this->modifyCsvForChecking($newCsvData);
        // If VISA, modify csv for upload
        if($accountName == "VISA") $newCsvData = $this->modifyCsvForVISA($newCsvData);

        // will be replaced when id's are determined
        $key_dummies = ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff', 'ggg', 'hhh', 'iii', 'jjj'];
        $max_splits = count($key_dummies);

        // Convert csv data to transaction records
        $newRecords = $this->convertCsv($newCsvData, $accounts, $accountName, $key_dummies, $max_splits);

        // add split totals to newRecords
        // need record ids, first.
        // get max id in transactions table
        $maxId = DB::table('transactions')
            ->whereNull('deleted_at')
            ->max('id');

        $nextId = $maxId + 1;
        $dummyToRealTotalKeyMap = [];
        $current_dummy_idx = 0;

        // assign ids, and fill in total_keys where needed
        foreach($newRecords as $newIdx=>$newRecord) {
            $newRecords[$newIdx]->id = $nextId;

            // if total_key is not a number, it is a dummy and needs to be replaced.
            if(isset($newRecord->total_key) && !is_numeric($newRecord->total_key)) {

                // is this a new mapping?
                $existingMap = $this->findArray($dummyToRealTotalKeyMap, $newRecord->total_key);

                // if new, mape a new map array
                // and set total_key to the nextId
                if($existingMap === false) {
                    $map = [$newRecord->total_key, $nextId];
                    $dummyToRealTotalKeyMap[] = $map;
                    $newRecords[$newIdx]->total_key = $nextId;
                } else {
                    // set total_key from map
                    $newRecords[$newIdx]->total_key = $existingMap[1];
                }
            }

            $nextId++;
        }
        
        // fill in split totals for new records
        $newRecords = $this->calcSplitTotals($newRecords);

        // Look for possible duplicate transactions
        $newRecords = $this->findDuplicates($newRecords);

        // Write new records to transactions table
        $newTransactions = $this->writeNewRecordsToTransactions($newRecords);
        foreach($newTransactions as $trxIdx=>$newTransaction) {
            $newTransactions[$trxIdx] = (array)$newTransaction; // for page (expecting arrays)
        }

        // get cars
        $cars = DB::table('carcostdetails')
            ->where('key', 'Purchase')
            ->whereNull('deleted_at')
            ->pluck('car');

        // TO DO:  order transactions by trans_date descending, toFrom ascending

        return view('transactions', ['accountName' => $accountName, 'newTransactions' => $newTransactions, 'transactions' => $transactions, 'accountNames' => $accountNames, 'accountIds' => $accountIds, 'lastStmtDates' => $lastStmtDates, 'tofromaliases' => $tofromaliases, 'toFroms' => $toFroms, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => true, 'beginDate' => $beginDate, 'endDate' => $endDate, 'clearedBalance' => '', 'registerBalance' => '', 'lastBalanced' => '', 'cars' => $cars]);
    }   // end of function upload


    // reads the csv file, massages, and writes to tolls table
    // tolls to upload should be in public/uploadFiles/tolls
    public function uploadtolls() {

        // READ toll records from csv file (only keeping what is needed)
        $tollRcds = $this->readTollCsv();

        // Write toll records to tolls table
        $result = DB::table('tolls')
                ->insert($tollRcds);

        if($result) {
            return response()->json([
                'message' => 'Tolls successfully written to tolls table. ' . $result,
                'status' => 'success'
            ]);
        } else {
            return response()->json([
                'message' => 'Unexpected error writing tolls to tolls table.',
                'status' => 'error'
            ]);
        }
    }   // end of function uploadtolls


    // sums tolls in tolls table for given trip & returns the sum of the tolls
    public function tallytolls(Request $request) {

        // get trip passed in
        $data = json_decode($request->getContent(), true);
        $trip = $data['trip'];

        // get sum of tolls (Outgoing) from tolls table for this trip
        $tolls = DB::table('tolls')
                ->where("trip", $trip)
                ->whereNull('deleted_at')
                ->get()->toArray();

        // from each toll record, sum total tolls; pass back each toll record for user to verify
        $sumTolls = 0;
        $tollRcds = [];
        foreach($tolls as $toll) {
            $sumTolls += $toll->Outgoing;
            $tollRcds[] = [
                $toll->{'Transaction Date'},
                substr($toll->{'Transaction Time'}, 0, 5),
                $toll->{'Exit Plaza'},
                $toll->{'Exit Lane'},
                $toll->Outgoing
            ];
        }

        // sort tolls by date and time, ascending
        usort($tollRcds, fn($a, $b) => [
            $a[0], // Transaction Date (ascending)
            substr($a[1], 0, 5), // Transaction Time (ascending)
        ] <=> [
            $b[0], // Transaction Date (ascending)
            substr($b[1], 0, 5) // Transaction Time (ascending)
        ]);

        // round the tolls total
        $sumTolls = round($sumTolls, 2);

        // if it's a number, return it
        if(is_numeric($sumTolls)) {
            return response()->json([
                'message' => 'Sum of tolls successfully retrieved from tolls table.',
                'status' => 'success',
                'tolls' => $sumTolls,
                'tollRcds' => json_encode($tollRcds)
            ]);
        // otherwise, return error
        } else {
            return response()->json([
                'message' => 'Unexpected error getting tolls sum from tolls table.',
                'status' => 'error'
            ]);
        }
    }   // end of function tallytolls


    // delete a transaction by id
    public function delete($id)
    {
        try {
            $response = DB::table('transactions')
                ->where("id", $id)
                ->whereNull('deleted_at')
                // ->delete();
                ->update(['deleted_at' => now(), 'copied' => 'needupt']);

            if($response == 1) {
                return response()->json([
                    'message' => 'Number records soft deleted: ' . $response,
                    'status' => 'success'
                ]);
            } else {
                return response()->json([
                    'message' => 'Unexpected number of records soft deleted: ' . $response,
                    'status' => 'error'
                ]);
            }

        } catch(\Exception $e) {
            error_log("\nProblem deleting transaction for id: " . $id);
            error_log(json_encode(['exception' => $e]));
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
        
    }

    // update a transaction
    public function update(Request $request)
    {
        try {
            // get transaction to update from payload
            $data = json_decode($request->getContent(), true);
            $transaction = $data['newTransaction'];
            error_log("transaction: " . $transaction);

            // remove url encoding
            $transaction = urldecode($transaction);

            // put it back as an object (from json)
            $transaction = json_decode($transaction);
            error_log("transaction: " . json_encode($transaction));
            $id = $transaction->id;

            // set fields to be updated
            $dataToUpdate = [
                'trans_date' => $transaction->trans_date,
                'clear_date' => $transaction->clear_date,
                'toFrom' => $transaction->toFrom,
                'amount' => $transaction->amount,
                'amtMike' => $transaction->amtMike,
                'amtMaura' => $transaction->amtMaura,
                'method' => $transaction->method,
                'category' => $transaction->category,
                'tracking' => $transaction->tracking,
                'stmtDate' => $transaction->stmtDate,
                'total_amt' => $transaction->total_amt,
                'total_key' => $transaction->total_key,
                'notes' => $transaction->notes,
                'copied' => $transaction->copied
            ];

            error_log(" ***** ");
            error_log("id: " . $id);
            error_log("dataToUpdate: " );
            foreach($dataToUpdate as $key=>$datum) error_log(" - " . $key . ": " . $datum);
            error_log(" ***** ");

            $response = DB::table("transactions")
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->update($dataToUpdate);

            return response()->json([
                'message' => "Transaction updated successfully"
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            error_log("Error updating transaction: " . $e->getMessage());
           
            // Re-throw the exception
            return response()->json([
                'error' => 'Failed to update transaction'
            ], 500);
        }
            
    }


    // update a investment account balances
    public function updateInvBalances(Request $request)
    {
        // reformats $date as yy-Mon (where Mon is a 3 char month abbrev)
        function formatStmtDate($date) {
            $dateTime = new \DateTime($date);
            $year = $dateTime->format('y');

            $fullMonthName = $dateTime->format('F');
            $abbrevMon = substr($fullMonthName, 0, 3);

            return "$year-$abbrevMon";
        }

        try {
            // get account info to update from payload
            $data = json_decode($request->getContent(), true);
            $newBalancesInfo = $data['newBalancesInfo'];

            // get today's date for the note in the database
            date_default_timezone_set('America/New_York');
            $today = date('Y-m-d');
            $note = "on " . $today;

            // set fields to be inserted that are the same for each record
            $newBalanceRcd = [
                'toFrom' => "Value",
                'category' => "Value",
                'notes' => $note,
                'lastBalanced' => $today
            ];

            // init records that will be inserted
            $recordsToInsert = [];

            // change data to be inserted for each element in newBalanceInfo
            foreach($newBalancesInfo as $idx=>$newBalanceInfo) {

                $newBalanceRcd['account'] = $newBalanceInfo['account'];
                $newBalanceRcd['trans_date'] = $newBalanceInfo['trans_date'];
                $newBalanceRcd['clear_date'] = $today;
                $newBalanceRcd['amount'] = $newBalanceInfo['amount'];
                $newBalanceRcd['amtMike'] = $newBalanceInfo['amount'] / 2;
                $newBalanceRcd['amtMaura'] = $newBalanceInfo['amount'] / 2;
                $stmtDate = formatStmtDate( $newBalanceInfo['trans_date']);
                $newBalanceRcd['stmtDate'] = $stmtDate;

                // build array of records to be inserted
                $recordsToInsert[] = $newBalanceRcd;
            }

            // insert the records
            $response = DB::table("transactions")
                ->insert($recordsToInsert);

            return response()->json([
                'message' => "Investment balances updated successfully"
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            logger()->error("Error inserting records to update investment balances: " . $e->getMessage());
            error_log("Error inserting records to update investment balances: " . $e->getMessage());
            
            // Re-throw the exception
            return response()->json([
                'error' => 'Failed to update investment balances'
            ], 500);
        }
            
    }   // end of function updateInvBalances


    // insert a new toFromAlias record
    public function insertAlias($origToFrom, $newValue) 
    {

        $origToFrom = urldecode($origToFrom);
        $newValue = urldecode($newValue);

        try {
            
            $response = DB::table('tofromaliases')
                ->insert([
                    'origToFrom' => $origToFrom,
                    'transToFrom' => $newValue,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                // 'recordId' => $record->id
            ], 200);
        } catch(\Exception $e) {
            error_log("\nProblem inserting tofromaliases record for origToFrom: " . $origToFrom . " and transToFrom: " . $newValue);
            error_log(json_encode(['exception' => $e]));
            \Log::error('Error inserting tofromaliases record.  ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        }

        // return response()->json([
        //     'message' => 'Record inserted successfully',
        //     'status' => 'success'
        // ]);
    }


    // insert a new transaction record
    public function insert(Request $request) 
    {
        $data = json_decode($request->getContent(), true);
        $transaction = urldecode($data['newTransaction']);
        $transaction = json_decode($transaction);

        // $transaction needs to be array, not object
        $transaction = (array)$transaction;

        // change null numeric values to 0
        foreach(["amount", "amtMike", "amtMaura"] as $field) {
            if($transaction[$field] == null) $transaction[$field] = 0;
        }

        try {
            // insert the transaction
            $response = DB::table('transactions')
                ->insert($transaction);
            $recordId = DB::getPdo()->lastInsertId();
            
            // if total_key is not numeric (it's a placeholder) and not null, update the total_key for all transactions with total_key matching current record
            if(!is_numeric($transaction['total_key']) && $transaction['total_key'] != null) {
                // the new total_key is the id of the newly saved transaction
                $newTotalKey = $recordId;
                $response = DB::table('transactions')
                    ->where('total_key', $transaction['total_key'])
                    ->whereNull('deleted_at')
                    ->update(['total_key' => $newTotalKey, 'copied' => 'needupt']);
            } else {
                $newTotalKey = false;
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction inserted successfully',
                'newTotalKey' => $newTotalKey,
                'recordId' => $recordId
            ], 200);
        } catch(\Exception $e) {
            error_log("\nProblem inserting transaction record");
            error_log(json_encode(['exception' => $e->getMessage()]));
            \Log::error('Error inserting transaction record.  ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        }
    }


    // get default values for a given toFrom
    public function getDefaults($account, $toFrom): JsonResponse
    {

        try {
            $accountId = DB::table('accounts')
                ->where('accountName', $account)
                ->whereNull('deleted_at')
                ->pluck('id');
            $accountId = $accountId[0];
            $lowerToFrom = strtolower($toFrom);

            $defaults = DB::table('tofromaliases')
                ->where('account_id', $accountId)
                ->whereRaw("LOWER(transToFrom) = ?", [$lowerToFrom])
                ->whereNull('deleted_at')
                ->first();

            // if none found, look in origToFrom
            if($defaults == null) {
                $defaults = DB::table('tofromaliases')
                ->where('account_id', $accountId)
                ->whereRaw("LOWER(origToFrom) = ?", [$lowerToFrom])
                ->whereNull('deleted_at')
                ->first();
            }

            return response()->json($defaults);
        } catch(\Exception $e) {
            error_log("\nProblem getting default values for toFrom: " . $toFrom . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // get clearedBalance, registerBalance, lastBalanced values for a given account
    public function getBalances($account): JsonResponse
    {

        try {
            $registerBalance = DB::table('transactions')
                ->where('account', $account)
                ->whereNull('deleted_at')
                ->sum('amount');

            $clearedBalance = DB::table('transactions')
                ->where('account', $account)
                ->whereNotNull('clear_date')
                ->whereNull('deleted_at')
                ->sum('amount');

            $lastBalanced = DB::table('transactions')
                ->where('account', $account)
                ->whereNull('deleted_at')
                ->max("LastBalanced");

            return response()->json([
                'register_balance' => $registerBalance,
                'cleared_balance' => $clearedBalance,
                'last_balanced' => $lastBalanced
            ]);
        } catch(\Exception $e) {
            error_log("\nProblem getting balances for account: " . $account . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // get total_key transactions
    public function totalKey($totalKey): JsonResponse
    {
        try {
            $totalKeyTransactions = DB::table('transactions')
                ->where('total_key', $totalKey)
                ->whereNull('deleted_at')
                ->select('id', 'amount', 'total_amt')
                ->get();
            return response()->json($totalKeyTransactions);
        } catch(\Exception $e) {
            error_log("\nProblem getting total_key transaction records for total_key: " . $totalKey . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // form to add new transaction
    public function addTransaction() {
        return view('addtransaction');
    }


    // insert new transaction
    public function writeTransaction(Request $request) {

        $transaction = [];
        $fields = [
            'trans_date',
            'clear_date',
            'account',
            'toFrom',
            'amount',
            'amtMike',
            'amtMaura',
            'method',
            'category',
            'tracking',
            'stmtDate',
            'total_amt',
            'total_key',
            'bucket',
            'notes'
        ];

        foreach($fields as $field) {
            if($request->input($field)) $transaction[$field] = $request->input($field);
            else $transaction[$field] = null;
        }
       
        DB::table('transactions')
            ->insert($transaction);

        return redirect()->route('accounts')->with("acctsMsg", "Transaction added.");

    }   // end of function writeTransaction


    // insert monthly transactions to transactions table for current month
    public function writeMonthlyTransactions(Request $request) {
        
        // note transactions done
        $transRecorded = [];

        // get inputs needed
        $chosens = $request->input('chosen');
        $names = $request->input('name');
        $transDates = $request->input('transDate');
        $accounts = $request->input('account');
        $toFroms = $request->input('toFrom');
        $amounts = $request->input('amount');
        $categorys = $request->input('category');
        $buckets = $request->input('bucket');
        $noteses = $request->input('notes');
        $doTrans = $request->input('dotrans');

        // used to redisplay monthlies with updated info
        $monthlies = $request->input('monthlies');
        $monthlies = json_decode($monthlies);

        // process each transaction, if chosen
        foreach($chosens as $idx=>$chosen) {

            if($chosen == 'true') {
                $transaction = [];
                $transaction['trans_date'] = $transDates[$idx];
                $transaction['account'] = $accounts[$idx];
                $transaction['toFrom'] = $toFroms[$idx];
                $transaction['amount'] = $amounts[$idx];
                $transaction['category'] = $categorys[$idx];
                $transaction['notes'] = $noteses[$idx];

                // set amtMike and amtMaura
                if($categorys[$idx] == 'MikeSpending' || $accounts[$idx] == 'Mike') {
                    $transaction['amtMike'] = $amounts[$idx];
                    $transaction['amtMaura'] = 0;
                } else if($categorys[$idx] == 'MauraSpending' || substr( $accounts[$idx], 0, 5) == 'Maura') {
                    $transaction['amtMaura'] = $amounts[$idx];
                    $transaction['amtMike'] = 0;
                } else {
                        $transaction['amtMaura'] = $amounts[$idx] / 2;
                        $transaction['amtMike'] = $amounts[$idx] / 2;
                }

                // set stmtDate
                $year = substr($transDates[$idx], 2, 2);
                $monthNumber = (int)substr($transDates[$idx], 5, 2 );
                $monthAbbrs = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $month = $monthAbbrs[$monthNumber];
                $transaction['stmtDate'] = $year . '-' . $month;

                // for Disc Savings, set bucket
                if($accounts[$idx] == 'DiscSavings') {
                    $transaction['bucket'] = $buckets[$idx];
                }

                // insert the transaction
                $result = DB::table('transactions')
                    ->insert($transaction);
         
                // update monthlies so it's reflected on the page
                $monthlies[$idx]->trans_date = $transaction['trans_date'];
                $monthlies[$idx]->status = 'Pending';

                // add this to transactions recorded
                //  (with reminder to DO the transaction, if needed)

                $transRecorded[] = [
                    'name' => $names[$idx],
                    'account' => $accounts[$idx],
                    'to_from' => $toFroms[$idx],
                    'amount' => $amounts[$idx],
                    'category' => $categorys[$idx],
                    'dotrans' => $doTrans[$idx]
                ];
            }
        }

        // reload the page with the new monthlies
        return view('monthlies', ['monthlies' => $monthlies, 'transRecorded' => $transRecorded]);

    }   // end of function writeMonthlyTransactions


    // update one change to retirement data
    public function writeRetirementDatum(Request $request) {

        // get inputs needed
        $data = json_decode($request->getContent(), true);
        $newValue = urldecode($data['newValue']);
        $fieldChanged = urldecode($data['fieldChanged']);
        $type = urldecode($data['type']);
        
        // set null strings to null; remove / in dates (stored in table without /s)
        if( $newValue == "") $newValue = null;
        elseif ($type == 'd') {
            $newValue = str_replace("/", "", $newValue);
        }

        // save new value (has been set to null if it's the same as the original value)
        $response = DB::table("retirementdata")
            ->where("description", $fieldChanged)
            ->update(["modified" => $newValue, 'copied' => 'needupt']);

        return response()->json([
            'success' => true,
            'message' => 'Retirement field updated.'
        ], 200);        // $chosens = $request->input('chosen');

    }   // end of function writeRetirementDatum


    // update/save all retirement data to be used
    public function writeRetirementInput(Request $request) {

        // get inputs needed
        $retirementInput = json_decode($request->getContent(), true);

        $descriptions = array_keys($retirementInput);
        // error_log("Descriptions: " . json_encode($descriptions));

        // delete old forecast inputs (type w/Inp)
        $response = DB::table('retirementdata')
            ->where('type', 'inpt')
            ->delete();
        // error_log("response from delete: " . $response);

        // insert new forecast inputs
        // create array to insert (with type "Inpt")
        $insertRetirementInput = [];
        foreach($descriptions as $description) {
            $retirementInput[$description] = str_replace("/", "", $retirementInput[$description]); // remove /s (specifically for dates)
            $retirementInput[$description] = str_replace(",", "", $retirementInput[$description]); // remove ,s (specifically for numbers)
            $insertRetirementInput[] = [
                'description' => $description,
                'data' => $retirementInput[$description],
                'type' => 'inpt'
            ];
        }
        // insert the array
        DB::table('retirementdata')
            ->insert($insertRetirementInput);

        return response()->json([
            'success' => true,
            'message' => 'Retirement input data updated.'
        ], 200);


        // set null strings to null; remove / in dates (stored in table without /s)
        if( $newValue == "") $newValue = null;
        elseif ($type == 'd') {
            $newValue = str_replace("/", "", $newValue);
        }

        // save new value (has been set to null if it's the same as the original value)
        $response = DB::table("retirementdata")
            ->where("description", $fieldChanged)
            ->update(["modified" => $newValue, 'copied' => 'needupt']);

        return response()->json([
            'success' => true,
            'message' => 'Retirement input data updated.'
        ], 200);

    }   // end of function writeRetirementInput


    // save changes to default monthly transactions info
    // public function saveMonthly($id, $name, $account, $dateOfMonth, $toFrom, $amount, $category, $bucket, $notes, $comments) {
    public function saveMonthly(Request $request, $id) {

        $monthlyTransaction = Monthly::find($id);
        \Log::info('Request Details:', [
            // 'headers' => $request->header(),
            // 'method' => $request->method(),
            // 'content_type' => $request->header('Content-Type'),
            // 'raw_content' => $request->getContent(),
            'all_data' => $request->all()
        ]);
        $result = $monthlyTransaction->update(
            $request->only(
                ['name', 
                'dateOfMonth',
                'trans_date',
                'account',
                'toFrom',
                'amount',
                'category',
                'bucket',
                'notes',
                'comments']
            )
        );

        Monthly::update(['copied' => 'needupt']);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully!'
        ]);
    }   // end of function saveMonthly


    // write transactions to database for GB Limo pay & spending
    // 6 transactions total:
    // - one for gross paycheck
    // - one for taxes withheld from paycheck
    // - two each for Mike/Maura Spending (4 total)
    // --- out of checking; into respective spending accts
    public function writeGBLimo(Request $request) {

        // transaction for gross paycheck deposit to checking
        //      as two transactions...
        //          1) IncomeMisc for taxable pay
        //          2) IncomeTaxFree for tips not taxed
        function writeGBgrossPay($request) {

            // write record for taxable income (IncomeMisc)
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbpaycheckdate');
            $transaction['clear_date'] = $request->input('gbpaycheckdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = "Great Bay Limo";
            // taxable includes taxable pay + tax w/h + ss w/h + medicare w/h
            $taxablePay = $request->input('gbtaxpay');
            $transaction['amount'] = $taxablePay;
            $transaction['amtMike'] = $taxablePay / 2;
            $transaction['amtMaura'] = $taxablePay / 2;
            $transaction['method'] = 'ACH';
            $transaction['category'] = 'IncomeMisc';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['total_amt'] = $request->input('gbnetpay');
            $transaction['total_key'] = "ggg";
            $transaction['notes'] = $request->input('gbpayperiodnote');
           
            $payId = DB::table("transactions")
                ->insertGetId($transaction);

            // set the total_key to the id of the record just inserted.
            DB::table("transactions")
                ->where("total_key", "ggg")
                ->whereNull('deleted_at')
                ->update(["total_key" => $payId, 'copied' => 'needupt']);


            // write record for tips income in paycheck (IncomeTaxFree)
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbpaycheckdate');
            $transaction['clear_date'] = $request->input('gbpaycheckdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = "Great Bay Limo";
            // gross includes taxable pay + tips (in paycheck) + tax w/h + ss w/h + medicare w/h
            $tipsPay = $request->input('gbtaxfreepay');
            $transaction['amount'] = $tipsPay;
            $transaction['amtMike'] = $tipsPay / 2;
            $transaction['amtMaura'] = $tipsPay / 2;
            $transaction['method'] = 'ACH';
            $transaction['category'] = 'IncomeTaxFree';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['total_amt'] = $request->input('gbnetpay');
            $transaction['total_key'] = $payId;
            $transaction['notes'] = $request->input('gbpayperiodnote');
           
            DB::table("transactions")
                ->insertGetId($transaction);

            return $payId;
        }   // end of function write GBgrossPy

        // transaction for tax withheld from deposit to checking and
        // transaction for ss and medicare withheld
        function writeGBtaxWH($request, $payId) {

            // tax withheld (if any)
            if($request->input('gbtaxwh') != 0) {
                $transaction = [];
                $transaction['trans_date'] = $request->input('gbpaycheckdate');
                $transaction['clear_date'] = $request->input('gbpaycheckdate');
                $transaction['account'] = "Checking";
                $transaction['toFrom'] = "Great Bay Limo";
                $transaction['amount'] = -$request->input('gbtaxwh');
                $transaction['amtMike'] = -$request->input('gbtaxwh') / 2;
                $transaction['amtMaura'] = -$request->input('gbtaxwh') / 2;
                $transaction['method'] = 'ACH';
                $transaction['category'] = 'IncomeTaxes';
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['total_amt'] = $request->input('gbnetpay');
                $transaction['total_key'] = $payId;
                $transaction['notes'] = $request->input('gbpayperiodnote');
            
                $result = DB::table("transactions")
                    ->insert($transaction);
                if(!$result) error_log("ERROR inserting TAX WITHHELD to transactions table: " . json_encode($result));
            }

            // ss & medicare withheld (if any)
            $otherWithheld = $request->input('gbsswh') + $request->input('gbmcwh');
            if($otherWithheld <> 0) {
                $transaction = [];
                $transaction['trans_date'] = $request->input('gbpaycheckdate');
                $transaction['clear_date'] = $request->input('gbpaycheckdate');
                $transaction['account'] = "Checking";
                $transaction['toFrom'] = "Great Bay Limo";
                $transaction['amount'] = -$otherWithheld;
                $transaction['amtMike'] = -$otherWithheld / 2;
                $transaction['amtMaura'] = -$otherWithheld / 2;
                $transaction['method'] = 'ACH';
                $transaction['category'] = 'IncomeOtherWH';
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['total_amt'] = $request->input('gbnetpay');
                $transaction['total_key'] = $payId;
                $transaction['notes'] = $request->input('gbpayperiodnote');
            
                $result = DB::table("transactions")
                    ->insert($transaction);
                if(!$result) error_log("ERROR inserting TAX WITHHELD to transactions table: " . json_encode($result));
            }

            return;
        }   // end of function writeGBtaxWH

        // write spending transactions for Mike or Maura ($MorM) (checking to spending)
        function writeGBspending($request, $MorM, $repaySpending) {

            // if spending is being repaid, no actual transfers to/from spending accounts happen,
            //      but they get virtually recorded (to keep spending balances accurate)
            if($repaySpending) $method = "Virtual";
            else $method = "Internet"; 

            // $MorM is either "Mike" or "MauraSCU"
            if($MorM == 'MauraSCU') $name = 'Maura';
            else $name = 'Mike';

            // Checking to spending record
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbspendingdate');
            $transaction['clear_date'] = $request->input('gbspendingdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = $MorM;
            $grossSpending = $request->input('gbnetpay') + $request->input('gbtaxwh');
            $transaction['amount'] = -$request->input('gbspending');
            $transaction['amtMike'] = -$request->input('gbspending') / 2;
            $transaction['amtMaura'] = -$request->input('gbspending') / 2;
            $transaction['method'] = $method;
            $transaction['category'] = 'ExtraSpending';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['notes'] = $request->input('gbspendingnote');
           
            DB::table("transactions")
                ->insert($transaction);

            // Spending from Checking record
            // Only make needed changes to transaction record
            $transaction['account'] = $MorM;
            $transaction['toFrom'] = "Checking";
            $transaction['amount'] = $request->input('gbspending');
            if($MorM == "Mike") {
                $transaction['amtMike'] = $request->input('gbspending');
                $transaction['amtMaura'] = 0;
            } else {
                $transaction['amtMaura'] = $request->input('gbspending');
                $transaction['amtMike'] = 0;
            }
            unset($transaction['category']);

            DB::table("transactions")
                ->insert($transaction);

            // if repay spending, create transactions to return the money to checking
            if($repaySpending) {
                // first, record for Checking...
                $transaction['account'] = "Checking";
                $transaction['toFrom'] = $MorM;
                $transaction['amount'] = $request->input('gbspending');
                if($MorM == 'Mike') {
                    $transaction['amtMike'] = $request->input('gbspending');
                    $transaction['amtMaura'] = 0;
                } else {
                    $transaction['amtMaura'] = $request->input('gbspending');
                    $transaction['amtMike'] = 0;
                }
                $transaction['method'] = $method;
                $transaction['category'] = $name . 'Spending';
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['notes'] = 'repay spending from GB pay';
            
                DB::table("transactions")
                    ->insert($transaction);


                // then, record for M/M spending...
                $transaction['account'] = $MorM;
                $transaction['toFrom'] = "Checking";
                $transaction['amount'] = -$request->input('gbspending');
                if($MorM == 'Mike') {
                    $transaction['amtMike'] = -$request->input('gbspending');
                    $transaction['amtMaura'] = 0;
                } else {
                    $transaction['amtMaura'] = -$request->input('gbspending');
                    $transaction['amtMike'] = 0;
                }
                unset($transaction['category']);
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['notes'] = 'repay checking for spending from GB pay';
            
                DB::table("transactions")
                    ->insert($transaction);
            }  // end of if repaySpending

            return;
        }   // end of function writeGBspending

        // write spending transactions for Mike or Maura ($MorM) (checking to spending)
        function writeTaxSetAside($request) {

            // Checking to income tax acct
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbspendingdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = "TaxDisc";
            $transaction['amount'] = -$request->input('gbtaxsetaside');
            $transaction['amtMike'] = -$request->input('gbtaxsetaside') / 2;
            $transaction['amtMaura'] = -$request->input('gbtaxsetaside') / 2;
            $transaction['category'] = 'Transfer';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['notes'] = "2026 GB Limo Tax";
           
            DB::table("transactions")
                ->insert($transaction);

            // Rcd for income tax acct
            // Only make needed changes to transaction record
            $transaction['account'] = "TaxDisc";
            $transaction['toFrom'] = "Checking";
            $transaction['amount'] = $request->input('gbtaxsetaside');
            $transaction['amtMike'] = $request->input('gbtaxsetaside') / 2;
            $transaction['amtMaura'] = $request->input('gbtaxsetaside') / 2;

            DB::table("transactions")
                ->insert($transaction);

            return;
        }   // end of function writeTaxSetAside

        // write records in transactions table for other tips received directly from clients
        // for MauraCash only (this app doesn't keep track of Mike's cash)
        function writeOtherTips($request) {

            // GB tips to Maura cash
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbspendingdate');
            $transaction['clear_date'] = $request->input('gbspendingdate');
            $transaction['account'] = "MauraCash";
            $transaction['toFrom'] = "GB tip";
            $transaction['amount'] = $request->input('gbothertips');
            $transaction['amtMike'] = 0;
            $transaction['amtMaura'] = $request->input('gbothertips');
            $transaction['stmtDate'] = $request->input('gbstmtdate');
           
            DB::table("transactions")
                ->insert($transaction);

            return;
        }   // end of function writeOtherTips

        // Is spending money for Mike/Maura to be immediately repaid to household checking?
        $repaySpending = $request->input('gbrepayspending');  // if this is "repay", then repay spending
        if($repaySpending == 'repay') $repaySpending = true;
        else $repaySpending = false;

        // transaction for gross paycheck deposit to checking
        // $payId is the id for the paycheck record,
        //      used as total_amt to tie this and the next record as a split transaction
        $payId = writeGBgrossPay($request);

        // transaction for ss, medicare, & tax withheld from deposit to checking
        writeGBtaxWH($request, $payId);

        // write spending transactions for Mike (checking to spending)
        writeGBspending($request, "Mike", $repaySpending);

        // write spending transactions for Maura (checking to spending)
        writeGBspending($request, "MauraSCU", $repaySpending);

        // write transactions to move money from ckg to income tax acct
        writeTaxSetAside($request);

        // if there were additional tips, write transactions for that.
        $gbothertips = $request->input('gbothertips');
        if($gbothertips != 0 && $gbothertips != null) {
            writeOtherTips($request);
        }

        // go back to accounts page, with reminder wrt transfer
        if($repaySpending) {
            $reminder = "REMEMBER to write transactions in checkbook (no transfer needed since Spending was repaid) \n\n";
        } else {
            $reminder = "REMEMBER to transfer " . $request->input('gbspending') . " each to Mike's and Maura's spending accounts,\n\nand write transactions in checkbook \n\n";
        }
        $reminder .= " and TRANSFER $" . $request->input('gbtaxsetaside') . " to Disc Tax acct (write that in checkbook, too).";

        return redirect()->route('accounts')->with('acctsMsg', $reminder);
    }   // end of writeGBLimo


    // write odometer reading to carcostdetails table
    function writeOdom($tripData) {
        $errMsg = null;

        $readingDate = $tripData['tripOdomDate'];
        $reading = $tripData['tripOdom'];
        $car = $tripData['tripCar'];

        $key = 'Mileage' . substr($readingDate, 2, 2) . substr($readingDate, 5, 2) . substr($readingDate, 8, 2);

        $insertRcd = [];
        $insertRcd['car'] = $car;
        $insertRcd['key'] = $key;
        $insertRcd['value'] = $reading;

        $result = DB::table('carcostdetails')
            ->insert($insertRcd);

        // check for error
        if(!$result) $errMsg = "Error writing odometer reading to carcostdetails table.  " . json_encode($result);

        return $errMsg;
    }   // end of writeOdom


    function calcSharePurchase($tripData, $purchase, $beginMiles, $expMiles) {

        // calc purchase cost per mile
        $costPerMile = round($purchase/($expMiles - $beginMiles), 3);

        // cost/mile * number of miles for this trip is the share of the purchase price for this trip
        $sharePurchase = round($costPerMile * $tripData["tripmiles"], 2);

        return $sharePurchase;

    }   // end of function calcSharePurchase


    // calculate share of maintenance costs for this trip
    function calcShareMaint($tripData, $beginMiles, $recentMileage) {

        // sum total cost of maintenance 2022 or later.
        // result is negative, so sign needs to be reversed.
        $recentMaint = DB::table('transactions')
            ->where('tracking', $tripData['tripCar'])
            ->where('notes', 'like', 'maint%')
            ->where('trans_date', '<=', $tripData['tripBegin'])
            ->whereNull('deleted_at')
            ->get()->toArray();
        
        // sum recent maint costs (those in transactions) for this car to $recentMaintTotAmt
        // and pull last $numbMaintTransToCheck maintenance transactions for this car in $checkRecentMaint
        $numbMaintTransToCheck = 5;
        $numRecentMaint = count($recentMaint);
        $checkRecentMaint = [];
        $recentMaintTotAmt = 0;
        foreach($recentMaint as $maintKey=>$maintRcd) {
            $recentMaintTotAmt -= $maintRcd->amount;
            if($maintKey >= $numRecentMaint-$numbMaintTransToCheck) {
                $checkRecentMaint[] = [
                    'Date' => $maintRcd->trans_date,
                    'Business' => $maintRcd->toFrom,
                    'Account' => $maintRcd->account,
                    'Tracking' => $maintRcd->tracking,
                    'Category' => $maintRcd->category,
                    'Notes' => $maintRcd->notes
                ];
            }
        }
        // error_log("*** recentMaintTotAmt: " . $recentMaintTotAmt);

        // sum maintenance before 2022 (not in transactions table) to $oldMaint
        $oldMaint = DB::table('carcostdetails')
            ->where('car', $tripData['tripCar'])
            ->where('key', 'OldMaint')
            ->whereNull('deleted_at')
            ->pluck('value');
        // error_log("*** oldMaint: " . $oldMaint);
        
        // if no old maintenance found, set to 0
        if(count($oldMaint) > 0) {
            $oldMaint = $oldMaint[0];
        } else {
            $oldMaint = 0;
        }
        // error_log("*** oldMaint: " . $oldMaint);
        
        // total maintenance is old + new
        $totMaint = $recentMaintTotAmt + $oldMaint;
        // error_log("*** totMaint: " . $totMaint);

        // calc maint cost per mile
        $costPerMile = $totMaint/($recentMileage - $beginMiles);
        
        // cost/mile * number of miles for this trip is the share of the maintenance cost for this trip
        $shareMaint = round($costPerMile * $tripData['tripmiles'], 2);

        return [$shareMaint, $checkRecentMaint];
        
    }   // end of function calcShareMaint


    // calculate share of insurance premium for this trip
    // NOTE:  This ASSUMES
    //
    //      IMPORTANT
    //      ASSUMPTION:  all ins data for the same term will be together in the db table!!!
    //
    //
    function calcShareIns($tripData, $beginMiles, $expMiles) {

        // get all insurance inforamtion
        // find what is relevant in the code below
        $insTableData = DB::table('carcostdetails')
            ->where('car', $tripData['tripCar'])
            ->where('key', 'like', 'InsPay%')
            ->whereNull('deleted_at')
            ->get()->toArray();

        // init vars to organize data
        $begins = [];
        $ends = [];
        $miles = [];
        $premiums = [];
        $prefixes = [];
        $errMsg = null;

        foreach($insTableData as $insDatum) {
            if(str_contains($insDatum->key, 'Begin')) {
                $begins[] = $insDatum->value;
            } else if(str_contains($insDatum->key, 'End')) {
                $ends[] = $insDatum->value;
            } else if(str_contains($insDatum->key, 'Miles')) {
                $miles[] = $insDatum->value;
            } else {
                $premiums[] = $insDatum->value;
                $prefixes[] = $insDatum->key;
            }
        }

        // find which prefix for the key to use
        $found = false;
        foreach($prefixes as $prefixIdx=>$prefix) {
            if(
                $tripData['tripBegin'] >= $begins[$prefixIdx] 
                && $tripData['tripBegin']<= $ends[$prefixIdx]
            ) {
                $found = true;
                break;
            }
        }

        if(!$found) {
            $errMsg = "No Insurance Premium for this time frame was found in the carcostdetails table.";
            $costPerMile = 0;
        } else {
            // $prefixIdx now has index into the relevant data for all the insurance info
            // calc maint cost per mile
            $costPerMile = $premiums[$prefixIdx]/$miles[$prefixIdx];
        }

        // cost/mile * number of miles for this trip is the share of the maintenance cost for this trip
        $shareIns = round($costPerMile * $tripData['tripmiles'], 2);

        return [$shareIns, $errMsg];
        
    }   // end of function calcShareIns


    // calculate fuel cost for this trip
    function calcFuel($tripData) {
    // NOTE:  This ASSUMES
    //
    //      IMPORTANT
    //      ASSUMPTION:  
    //          All gas/charging done is in the database
    //              with notes like 'trips - <trip name>'
    //              and gallons or kWhs purchased
    //

    // Need:
    //      miles traveled  
    //          $tripData['tripMiles']
    //      gas/kWh purchased en route
    //          in transactions table where notes = "% - trips - <tripName> ..."
    //      MPG or mile/kwh
    //          in carcostdetails table
    //              key = "Fuel" tells if gas or electric is used
    //              for electric: solar kWh/mile is where key = "MPK"
    //              for gas: mpg is where key = "MPG"
    //      cost of fuel not bought on trip
    //          electric: in carcostdetails table where key = "SolarKwh"
    //          gas:    in transactions table, last where 


        // get data keys needed in carcostdetails
        function getCarCostData($tripData) {
            $errMsg = null;     // assume no msgs to start

            // keys in carcostdetails table for data needed
            $keys = [
                'Fuel',
                'MPK',
                'MPG',
                'SolarKwh'
            ];

            // get data
            $carCostInfo = DB::table('carcostdetails')
                ->where('car', $tripData['tripCar'])
                ->whereIn('key', $keys)
                ->whereNull('deleted_at')
                ->get()->toArray();
            // error_log("------ carCostInfo:");
            // error_log(json_encode($carCostInfo));

            // put in more usable format
            $fuel = null;
            $MPG = null;
            $MPK = null;
            $SolarKwh = null;

            foreach($carCostInfo as $info) {
                switch ($info->key) {
                    case 'Fuel':
                        $fuel = $info->value;
                        break;
                    case 'MPG':
                        $MPG = $info->value;
                        break;
                    case 'MPK':
                        $MPK = $info->value; 
                        break;
                    case 'SolarKwh':
                        $SolarKwh = $info->value;
                        break;
                    default:
                        $errMsg = '\n\n  NOTE:  *** Undefined carCostInfo key found: ' . $info->key . "\n\n";
                        break;
                }
            }

            return [$fuel, $MPK, $MPG, $SolarKwh, $errMsg];

        }  // end of function getCarCostData


        // get fuel bought info (volume and cost)
        function getFuelBoughtInfo($tripData, $fuel) {
            $msg = null; // assume no msgs to start

            // gets gas bought, if any
            $gasBought = DB::table('transactions')
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', '%gas% trips - ' . $tripData['tripName'] . '%')
                ->whereNull('deleted_at');

            // gets kwh bought, if any
            $kwhBought = DB::table('transactions')
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', '%charg% trips - ' . $tripData['tripName'] . '%')
                ->whereNull('deleted_at');

            // all fuel bought (should be just all gas or kwh)
            $fuelBoughtEnRoute = $gasBought->unionAll($kwhBought)->get()->toArray();
            error_log("fuelBoughtEnRoute: ");
            error_log(json_encode($fuelBoughtEnRoute));

            // put in usable format
            $fuelVolumeEnRoute = 0;
            $fuelCostEnRoute = 0;
            $needUnitCost = false;
            foreach($fuelBoughtEnRoute as $fuelEvent) {
                [$cost, $vol, $unitCost, $msg] = findFuelCostAndAmt($fuelEvent, $fuel, $needUnitCost); // unitCost will be null
                $msg .= $msg;
                $fuelVolumeEnRoute += $vol;
                $fuelCostEnRoute -= $cost;      // costs are negative in transactions table, positive here
            }

            return [$fuelVolumeEnRoute, $fuelCostEnRoute, $msg];
        }   // end of function getFuelBoughtInfo


        // get how much purchase (amt) and what cost from record where fuel was purchase en route
        function findFuelCostAndAmt($fuelEvent, $fuel, $needUnitCost) {
            $msg = '';  // assume no msg's until something found.
            error_log("findFuelCostAndAmt... passed in:");
            error_log("  fuelEvent: " . json_encode($fuelEvent) . ";\n  fuel: " . $fuel . ";\n needUnitCost: " . ($needUnitCost ? 'true' : 'false'));

            if(!$needUnitCost) {
                error_log("in !needUnitCost block");
                // get cost of fuel from record
                $cost = $fuelEvent->amount;
                error_log("cost: " . $cost);

                // get amt from "notes" column
                if($fuel == 'electric') {
                    // needs to have " ##.## kwh"
                    $volPattern = '/(\d+(?:\.\d+)?)\s*kwh/i';

                } else if ($fuel == 'gas') {
                    // needs to have " ##.## gal"
                    $volPattern = '/(\d+(?:\.\d+)?)\s*per gal/i';
                }

                preg_match($volPattern, $fuelEvent->notes, $matches);
                error_log("matches: " . json_encode($matches));
                // Get the matched number
                if($fuel == 'gas') {
                    $unitCost = -$matches[1]; // Will contain string of volume purchased
                    $amt = $cost/$unitCost;
                } else if($fuel == 'electric') {
                    $amt = $matches[1];
                }
                error_log("amt (from matches): " . $amt);
                if($amt == '' || $amt == null) {
                    $msg = "No amount found.";
                } else {
                    // Convert to float if needed
                    $amt = floatval($amt);
                }
            } else {
                $cost = null; // not requested
                $amt = null;  // not requested
            }
            error_log("amt (in findFuelCostAndAmt): " . $amt);
           
            if($needUnitCost) {
                // get unit cost
                $unitPattern = '/@ *([\d.]+)/';
                preg_match($unitPattern, $fuelEvent->notes, $matches);

                if ($matches) {
                    $unitCost = $matches[1];
                } else {
                    $unitCost = null;
                    $msg .= "  No unit cost found.";
                }
            } else {
                $unitCost = null;   // not requested
            }

            error_log("in findFuelCostAndAmt, returning.. \n  cost: " . $cost . "\n  amt: " . $amt . "\n  unitCost: " . $unitCost . "\n  msg: " . $msg);
            return [$cost, $amt, $unitCost, $msg];
        }   // end of function findFuelCostAndAmt
    
    
        $errMsg = null;

        // get data keys needed in carcostdetails
        [$fuel, $MPK, $MPG, $SolarKwh, $errMsg] = getCarCostData($tripData);
        // error_log("\n\nfuel: " . $fuel . "\nMPG: " . $MPG . "\nMPK: " . $MPK . "\nSolarKwh: " . $SolarKwh . "\nerrMsg: " . $errMsg);

        // get fuel bought info (volume and cost)
        [$fuelVolumeEnRoute, $fuelCostEnRoute, $msg] = getFuelBoughtInfo($tripData, $fuel);
        if($msg != null) $errMsg .= "  " . $msg;

        // get last time gas was bought (not on a trip) BEFORE this trip
        // if($tripData['tripCar'] == 'CRZ') {
        if($fuel == 'gas') {
            $lastGases = DB::table('transactions')
                ->where('trans_date', '<=', $tripData['tripBegin'])
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', 'gas%')
                ->where('notes', 'not like', '%- trips %')
                ->where('notes', 'not like', '%- trip %')
                ->whereNull('deleted_at')
                ->orderBy('trans_date', 'desc')
                ->get()->toArray();
            // need at least one lastGases record
            if(count($lastGases) == 0) {
                $errMsg .= "Need to be able to see how much was paid for gas recently.\n\n";
                $recentGasCost = 0;
                $recentGasVolume = 0;
                $recentUnitPrice = 0;
            } else {
                $lastGas = $lastGases[0];

                $needUnitCost = true;
                // recentGasCost and recentGasVolume not needed, should be null
                [$recentGasCost, $recentGasVolume, $recentUnitPrice, $msg] = findFuelCostAndAmt($lastGas, $fuel, $needUnitCost); 
                error_log("------ recentUnitPrice:");
                error_log(json_encode($recentUnitPrice));
                $errMsg .= $msg;

                // error_log("-- recentUnitPrice: " . $recentUnitPrice);
            }
        }

        // Have data needed to calc fuel cost.
        if($fuel == 'electric') {
            // Charging en route + Home charging
            // Need est kWh used: Total miles / MPK
            $totalKwhUsed = $tripData['tripmiles'] / $MPK;
            $gallonsKwHused = $totalKwhUsed;

            // fuel not purchased en route
            $fuelVolumeNotBoughtEnRoute = $totalKwhUsed - $fuelVolumeEnRoute;
            error_log("fuelVolumeNotBoughtEnRoute: " . $fuelVolumeNotBoughtEnRoute);
            
            // cost of fuel not bought en route
            $fuelCostNotBoughtEnRoute = $fuelVolumeNotBoughtEnRoute * $SolarKwh/100;
            error_log("fuelCostNotBoughtEnRoute: " . $fuelCostNotBoughtEnRoute);

            // total fuel cost = bought en route + not bought en route
            $fuelCost = $fuelCostEnRoute + $fuelCostNotBoughtEnRoute;
            error_log("total fuel volume: " . ($fuelVolumeEnRoute + $fuelVolumeNotBoughtEnRoute));

        } else if($fuel == 'gas') {
            // Gas bought en route + gas already in tank that was used
            // Need est gallons used: Total miles / MPG
            $totalGalUsed = $tripData['tripmiles'] / $MPG; 
            $gallonsKwHused = $totalGalUsed;   

            // fuel not purchased en route
            $fuelVolumeNotBoughtEnRoute = $totalGalUsed - $fuelVolumeEnRoute;
            error_log("---");
            error_log("totalGalused: " . $totalGalUsed);
            error_log("fuelVolumeEnRoute: " . $fuelVolumeEnRoute);
            error_log("fuelVolumeNotBoughtEnRoute: " . $fuelVolumeNotBoughtEnRoute);
            error_log("---");
            
            // cost of fuel not bought en route
            $fuelCostNotBoughtEnRoute = $fuelVolumeNotBoughtEnRoute * $recentUnitPrice;
            
            error_log("fuel cost bought en route (gas): " . $fuelCostEnRoute);
            error_log("fuel cost not bought en route (gas): " . $fuelCostNotBoughtEnRoute);
            error_log("total cost (gas): " . ($fuelCostEnRoute + $fuelCostNotBoughtEnRoute));
            error_log("---");

            // total fuel cost = bought en route + not bought en route
            $fuelCost = $fuelCostEnRoute + $fuelCostNotBoughtEnRoute;

        } else $errMsg .= "  Invalid fuel found on carCostDetails table.";

        // $errMsg = trim($errMsg);
        // error_log("errMsg (end of calcFuel): " . $errMsg);
        return [round($fuelCost,2), round($gallonsKwHused,2), $errMsg];
        
    }   // end of function calcFuel


    // calc values & write transactions for cost of car for a trip
    // 2 transactions total (in & out of household checking) -- from MxxxSpending to IncomeMisc categories
    // - one from Spending (MikeSpending and/or MauraSpending) category (to charge who used the car)
    // - second to Checking with category IncomeMisc (because money never really left the ckg account)
    public function recordTrip(Request $request) {

        // Do we have all the info?  Assume complete until find something missing
        $completeTripInfo = true;
        // error_log("completeTripInfo (init): " . $completeTripInfo);

        // in case there are messages
        $errMsg = null;

        // fields to retrieve (ids) from page (form)
        $fields=[
            "tripName",
            "tripBegin",
            "tripEnd",
            "tripWho",
            "tripCar",
            "tripOdom",
            "tripOdomDate",
            "tripmiles",
            "tripTolls"
        ];

        foreach($fields as $field) {
            if($request->input($field)) $tripData[$field] = $request->input($field);
            else $tripData[$field] = null;
        }
        // error_log("tripData: " . json_encode($tripData));

        // write odometer reading, if it was entered
        if($tripData['tripOdom'] != '' && $tripData['tripOdomDate'] != '') {
            $msg = $this->writeOdom($tripData);
            // Go no further if error recording odometer reading
            if($msg != null) {

                // get cars, drivers, and most recent mileage from carcostdetails table
                $carInfo = $this->getCarInfo();

                // get an array of all the trip names
                $tripNames = $this->getTripNames();

                $errMsg = '**** No TRIP recorded.  Need correct odometer reading! ****';
                return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames, 'errMsg' => ($msg . "\n" . $errMsg)]);
            }
        }

        // Go no further if tripEnd is before tripBegin
        if($tripData['tripEnd'] < $tripData['tripBegin']) {

            // get cars, drivers, and most recent mileage from carcostdetails table
            $carInfo = $this->getCarInfo();

            // get an array of all the trip names
            $tripNames = $this->getTripNames();
        
            $errMsg = '**** No TRIP recorded.  Trip must begin before it ends! ****';
            return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames,'errMsg' => $errMsg]);
        }

        // get data needed from carcostdetails table
        // get purchase price of car, begin mileage & est total (end) mileage
        $dataNeeded = DB::table('carcostdetails')
            ->select("key", "value")
            ->where("car", $tripData["tripCar"])
            ->whereIn("key", ["Purchase", "BeginMiles", "ExpMiles"])
            ->whereNull('deleted_at')
            ->get()->toArray();

        // get most recent mileage
        $recentMileage = DB::table('carcostdetails')
            ->select("key", "value")
            ->where("car", $tripData["tripCar"])
            ->where("key", "like", "Mileage%")
            ->whereNull('deleted_at')
            ->max("value");
        // error_log("recentMileage: " . json_encode($recentMileage));

        // pull data out of results
        foreach($dataNeeded as $dataRcd) {
            switch($dataRcd->key) {
                case "Purchase":
                    $purchase = $dataRcd->value;
                    break;
                case "BeginMiles";
                    $beginMiles = $dataRcd->value;
                    break;
                case "ExpMiles";
                    $expMiles = $dataRcd->value;
                    break;
            }
        }

        // calc different costs
        
        // share of purchase price
        $tripData["sharePurchase"] = $this->calcSharePurchase($tripData, $purchase, $beginMiles, $expMiles);
        
        // share of maintenance costs
        [$tripData["shareMaint"], $checkRecentMaint] = $this->calcShareMaint($tripData, $beginMiles, $recentMileage);

        // add checkRecentMaint to errMsg
        $msgIntro = "If these are NOT the most recent maint transactions, add those and re-calc the trip:";
        $errMsg .= "\n" . $msgIntro . "\n";
        foreach($checkRecentMaint as $maintTrans) {
            foreach($maintTrans as $key=>$maintItem) {
                $errMsg .= " - " . str_pad($key . ":", 10) . "\t" . $maintItem . "\n";
            }
            $errMsg .= "\n";
        }

        // share of insurance payments
        [$tripData["shareIns"], $msg] = $this->calcShareIns($tripData, $beginMiles, $expMiles);
        if($msg != null) $completeTripInfo = false;
        // error_log("completeTripInfo (sharIns): " . $completeTripInfo);

        $errMsg .= $msg;
        
        // fuel (gas or charging) for this trip
        //      handle gas/charging purchased during trip
        [$tripData["fuelCost"], $tripData['gallonsKwHused'], $msg] = $this->calcFuel($tripData);
        if($msg != null) $completeTripInfo = false;
        // error_log("completeTripInfo (calcFuel): " . $completeTripInfo);

        $errMsg = $msg . $errMsg;
        // error_log("errMsg (after calcFuel): " . $errMsg);

        // error_log("Fuel cost: " . $tripData['fuelCost']);
        // error_log("gallonsKwHused: " . $tripData['gallonsKwHused']);

        // error_log("errMsg:" . $errMsg);
        $newTripRcd = [];
        $newTripRcd['trip'] = $tripData['tripName'];
        $newTripRcd['who'] = $tripData['tripWho'];
        $newTripRcd['car'] = $tripData['tripCar'];
        $newTripRcd['begin'] = $tripData['tripBegin'];
        $newTripRcd['end'] = $tripData['tripEnd'];
        $newTripRcd['tolls'] = $tripData['tripTolls'] ?? 0;
        $newTripRcd['mileage'] = $tripData['tripmiles'];
        $newTripRcd['sharePurchase'] = $tripData['sharePurchase'];
        $newTripRcd['shareIns'] = $tripData['shareIns'];
        $newTripRcd['shareMaint'] = $tripData['shareMaint'];
        $newTripRcd['gallonsKwHused'] = $tripData['gallonsKwHused'];
        $newTripRcd['gasChargingDollars'] = $tripData['fuelCost'];
        $newTripRcd['other'] = 0;

        // write record to the trips table.
        $result = DB::table("trips")->insert($newTripRcd);
        if($result) error_log("TRIPS record written");
        else error_log("TRIPS record NOT written: " . json_encode($result));

        // trip errMsg
        // $errMsg = trim($errMsg);
        // error_log("completeTripInfo (end): " . $completeTripInfo);

        if($completeTripInfo) {
            $errMsg = "\n**** Trip recorded. Total cost was " 
            . ($tripData['tripTolls'] + $tripData['sharePurchase'] + $tripData['shareIns'] + $tripData['shareMaint'] + $tripData['fuelCost'] + ($tripData['other'] ?? 0))
            . ". ****\n" 
            . "**** REMEMBER!!! Transfer the cost of the trip from Mike/Maura-Spending!\n\n"
            . $errMsg;
        } else {
            $errMsg = "PARTIAL trip recorded.\n\n" 
            . "May need to fix problems and re-calc trip."
            . "\nIf not, REMEMBER to transfer the cost of the trip from Mike/Maura-Spending!\n\n"
            . $errMsg;
        }

        // load trips page with msg containing what was done, errors, warning.
        // get cars, drivers, and most recent mileage from carcostdetails table
        $carInfo = $this->getCarInfo();

        // get an array of all the trip names
        $tripNames = $this->getTripNames();

        return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames,'errMsg' => $errMsg]);


    }   // end of recordTrip
    

    // Show assets and current values
    public function assets($endDate = null) {

        // Input param: 
        //  accounts - all transaction records for accounts
        //  array of accountNames to process
        //
        // Returns:
        //  only those records with the most recent lastBalanced value
        function getLatest($accounts, $accountNames) {

            // init arrays to keep track of most recent lastBalanced info
            // newAccounts - records with the most recent data for each account
            // lastBalancedDates - most recent lastBalanced date found for each account
            $newAccounts = [];
            $lastBalancedDates = [];

            // init these arrays
            foreach($accountNames as $accountName) {
                $newAccounts[$accountName] = null;
                $lastBalancedDates[$accountName] = null;
            }

            foreach($accounts as $account) {

                // get the account name
                $accountName = $account->account;

                // if this is the first record encountered for this account, keep the info
                if($newAccounts[$accountName] == null) {
                    $newAccounts[$accountName] = $account;
                    $lastBalancedDates[$accountName] = $account->max_last_balanced;
                // if this record's lastBalanced is later than the one saved, replace the old data with this
                } else if(
                    $lastBalancedDates[$accountName] == null ||
                    $lastBalancedDates[$accountName] < $account->max_last_balanced
                ) {
                    $lastBalancedDates[$accountName] = $account->max_last_balanced;
                    $newAccounts[$accountName] = $account;
                }
                    
            }

            // fill in default values for null records
            $accounts = [];
            foreach($newAccounts as $key=>$account) {
                if($account != null) {
                    $accounts[] = $account;
                } else {
                    $accounts[$key] = (object) [
                        'account' => $account,
                        'amount' => 0,          // amount
                        'lastBalanced' => date("Y-m-d"),  // last balanced
                        'type' => ''
                    ];
                }
            }

            // don't need high level keys
            $newAccounts = array_values($newAccounts);
            
            // return just the records with the most recent lastBalanced dates (one / account)
            return $accounts;

        }

        // get total assets
        function getTotalAssets($accounts) {
            $totalLine = [];
            $totalLine['account'] = 'Total';
            $totalLine['amount'] = array_sum(array_column($accounts, 'amount'));
            $totalLine['max_last_balanced'] = max(array_column($accounts, 'max_last_balanced'));
            $totalLine['type'] = "Total";

            return (object) $totalLine;
        }

        // set endDate if not passed in
        if($endDate == null) {
            $endDate = date('Y-m-d');
        }

        // get all transactional account names
        $transAccountNames = DB::table('accounts')
            ->where("type", "trans")
            ->whereNull('deleted_at')
            ->distinct()->get("accountName")->toArray();
        $transAccountNames = array_column($transAccountNames, 'accountName');

        // get all investment account names
        $invAccountNames = DB::table('accounts')
            ->where("type", "inv")
            ->whereNull('deleted_at')
            ->distinct()->get("accountName")->toArray();
        $invAccountNames = array_column($invAccountNames, 'accountName');

        // get record for each transactional account
        $transAccounts = DB::table('transactions')
            ->select(
                'account',
                // DB::raw('SUM(CASE WHEN clear_date <= ' . $endDate . ' THEN amount ELSE 0 END) AS amount'),
                DB::raw('SUM(amount) AS amount'),
                DB::raw('MAX(lastBalanced) AS max_last_balanced'),
                DB::raw("'Trans' AS type")
            )
            ->whereIn(
                'account',
                $transAccountNames
            )
            ->whereDate('clear_date', '<=', $endDate)       // new
            ->whereNull('deleted_at')
            ->groupBy('account')
            ->get()
            ->toArray();
        
        // order trans accounts alphabetically
        usort($transAccounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        // get record for each investment account
        $invAccounts = DB::table('transactions')
            ->select(
                'account',
                'amount',
                'lastBalanced AS max_last_balanced',
                DB::raw("'Inv' AS type"),
            )
            ->where('category', "VALUE")
            ->whereIn(
                'account',
                $invAccountNames
            )
            ->where('clear_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();

        // get transaction with latest clear_date for each account
        $invAccounts = getLatest($invAccounts, $invAccountNames);

        // order inv accounts alphabetically
        usort($invAccounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        // merge the accounts arrays
        $accounts = array_merge($transAccounts, $invAccounts);

        // add Total line to $accounts
        $accounts[] = getTotalAssets($accounts);

        // format $ amounts  for each account
        foreach($accounts as $acctIdx=>$account) {
            $accounts[$acctIdx]->amount = number_format($account->amount, 2);
        }

        return view('assets', ['accounts' => $accounts]);
    }   // end of function assets


    // Prompt for input needed, and process GB Limo paycheck
    public function gblimo() {

        // get defaults for page

        // paycheck date - one week since last one
        $gboldpaycheckdate = DB::table('transactions')
            ->where('toFrom', 'Great Bay Limo')
            ->whereNull('deleted_at')
            ->max('trans_date');

            // add a week to it
        $newpaycheckdate = new \DateTime($gboldpaycheckdate);
        $newpaycheckdate->modify("+1 week");
        $newpaycheckdate = $newpaycheckdate->format("Y-m-d");
        
        // spending transfer date (defaults to today)
        $timezone = new \DateTimeZone('America/New_York');
        $today = new \DateTime('now', $timezone);
        $gbspendingdate = $today->format("Y-m-d");

        // statement date can default to the spending date

        return view('gblimo', ['gbpaycheckdate' => $newpaycheckdate, 'gbspendingdate' => $gbspendingdate, 'gbstmtdate' => $gbspendingdate]);

    }   // end of function gblimo


    // Set up & do monthly transactions
    public function monthly() {

        // get monthly transactions set up
        $monthlies = DB::table('monthlies')
            ->whereNull('deleted_at')
            ->get()->toArray();

        // get last cleared_date (or last trans_date if cleared is null) for each monthly transaction
        // if cleared_date is null, transaction is Pending, else it is Completed.
        foreach($monthlies as $monIdx=>$month) {
            // basic query to get most recent monthly transactions
            $dates = DB::table('transactions')
                ->where('toFrom', $month->toFrom)
                ->where('account', $month->account)
                ->where('notes', 'LIKE', $month->notes . '%')
                ->whereNull('deleted_at')
                // ->where('amount', $month->amount)        -- only do this (below) when amount is consistent
                // ->where('category', $month->category)    -- only do this (below) if category is consistent
                ->select(
                    DB::raw("CASE 
                        WHEN clear_date IS NOT NULL THEN clear_date 
                        ELSE trans_date 
                    END as date"),
                    DB::raw("CASE 
                        WHEN clear_date IS NOT NULL THEN 'Completed'
                        ELSE 'Pending'
                    END as status")
                )
                ->orderByRaw("CASE 
                    WHEN clear_date IS NOT NULL THEN clear_date
                    ELSE trans_date
                END DESC") // puts the most recent first
                ->get();

            // if amount is set, filter by that
            if(!is_null($month->amount)) {
                $dates->where('amount', $month->amount);
            }

            // match category, if needed
            if(!$month->anyCategory) {
                $dates->where('category', $month->category);
            }

            // get the first transaction (they've been sorted by most recent first)
            $dates = $dates->first();

            if($dates == NULL) {
                // this shouldn't happen - means there's probably an error in the database
                $monthlies[$monIdx]->trans_date = 'not found';
                $monthlies[$monIdx]->status = 'unknown';
            } else {
                // add dates and statuses to monthlies array
                $monthlies[$monIdx]->trans_date = $dates->date;
                $monthlies[$monIdx]->status = $dates->status;
            }

        }

        // Sort by status and regular date to display in an orderly fashion
        usort($monthlies, function($a, $b) {
            if ($a->status === $b->status) {
                return (int) $a->dateOfMonth > (int) $b->dateOfMonth;
            }
            return strcmp($a->status, $b->status);
        });
        
        // no recorded transactions to show here
        return view('monthlies', ['monthlies' => $monthlies, 'transRecorded' => [] ]);
    }   // end of function monthly


    // Retirement analysis
    public function retirementInput() {
        // get existing retirement data:
        //       savingsBalance      Big Bills (Disc)
        //       checkingBalance     SCU checking
        //       retirementDisc      Disc
        //       retirementTIAA      TIAA CREF
        //       retirementWFIRA     Total WF IRAs
        //       retirementWFinv     WF non-IRA
        //       retirementEJ        EJ
        //       LTC                 Inherited IRA + Disc LTC (input for Inherited; Disc LTC separate)
        //      incomeNHRet         last NH Retirement deposit
        //      incomeSSMike        last SS dep for Mike
        //      incomeSSMaura       last SS dep for Maura (or retirement table or input)
        //      incomeIBMMike       last IBM dep for Mike
        //      incomeIBMMaura      last IBM dep for Maura (or retirement table or input)
        //      incomeGBLimoYTD     sum from transactions table
        //      incomeGBLimoAnnual  pro-rated for year (or max allowed by SS when <65)
        //      incomeTownYTD       sum from transactions table
        //      incomeTownAnnual    pro-rated for year
        //      incomeRentalYTD     sum from transactions table
        //      houseAssessed       from retirement table or input
        //      houseEqRatio        from retirement table or input

        function getRetirementData() {
            // get constants, assumptions, etc. from retirementdata table
            // get retirement income
            $beginOfMonth = $date = date('Y-m-01');;

            $dbdata = DB::table('retirementdata')
                ->select('id', 'description', 'type', 'data', 'UOM', 'modified', 'updated_at')
                ->whereNull('deleted_at')
                ->orderBy('type', 'asc')
                ->orderBy('order', 'asc')
                ->get()->toArray();

            // get in useful formate
            // each element in each of these arrays is a 4 element array with:
            //      order,  value,  unit of measure (UOM),   date of value (may be null if n/a)
            // EXCEPT retirementDataInfo - which just has the json for that description
            $retirementDataAcctNums = [];
            $retirementDataAssumptions = [];
            $retirementDataConstants = [];
            $retirementDataIncomes = [];
            $retirementDataValues = [];
            $retirementDataBalances = [];
            $retirementDataRents = [];
            $retirementDataInfo = [];

            // current year & month - delete rental income before this
            $yearMonth = date("y") . date("m");

            foreach($dbdata as $dataPoint) {
                $date = substr( $dataPoint->updated_at, 0, 10);
                switch ($dataPoint->type) {
                    case 'acct':
                        $retirementDataAcctNums[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->modified];
                        break;
                    case 'assm':
                        $retirementDataAssumptions[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->modified];
                        break;
                    case 'con':
                        $retirementDataConstants[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->modified];
                        break;
                    case 'inc':
                        $retirementDataIncomes[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->modified];
                        break;
                    case 'val':
                        $retirementDataValues[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->modified];
                        break;
                    case 'rent':
                        $retirementDataRents[$dataPoint->description] = [$dataPoint->data, $dataPoint->UOM, $date, $dataPoint->id];
                        break;   
                    case 'info':
                        $retirementDataInfo[$dataPoint->description] = $dataPoint->data;
                        break;   
                }
            }   
            error_log("test 4 -- retirementDataInfo: " . json_encode($retirementDataInfo));

            // accounts that need to sum transactions to get current balance
            $sumAccountsDB = json_decode($retirementDataInfo['sumAccountsDB']);
            $sumAccountsVerbiage = json_decode($retirementDataInfo['sumAccountsVerbiage']);
            // error_log("sumAccountsDB: " . json_encode($sumAccountsDB));

            // accounts to find latest balanced entered in DB
            $lastBalanceDB = json_decode($retirementDataInfo['lastBalanceDB']);
            $lastBalanceString = '"'. implode('", "', $lastBalanceDB) . '"';
            // error_log("lastBalanceDB: " . json_encode($lastBalanceDB));

            // accounts to find last deposit
            $lastDeposit = json_decode($retirementDataInfo['lastDeposit']);

            // get sum of balances
            // Savings, Checking, Discover Retirement (DiscRet)
            $dbbalances = DB::table('transactions')
                ->selectRaw('SUM(amount) as amount, account, max(clear_date) as date')
                ->whereIn('account',$sumAccountsDB)
                ->where('trans_date', '<', $beginOfMonth)
                ->whereNull('deleted_at')
                ->groupBy('account')
                ->get()->toArray();

            $balances = [];
            foreach($dbbalances as $balance) {
                $balances[$balance->account] = [$balance->amount, "$", $balance->date, null];
            }
            // error_log("dbbalances 1:");
            // foreach($dbbalances as $bal) error_log(json_encode($bal));

            foreach($sumAccountsDB as $key => $acct) {
                $retirementDataBalances[$sumAccountsVerbiage[$key]] = $balances[$acct];
            }
            // error_log("retirementDataBalances:");
            // foreach($retirementDataBalances as $data) error_log(json_encode($data));

            // get latest balances
            $dbbalances = DB::table('transactions as t1')
                ->join(DB::raw('(SELECT account, MAX(clear_date) as max_date
                    FROM transactions 
                    WHERE account IN (' . $lastBalanceString . ')
                    AND deleted_at IS NULL
                    GROUP BY account) as t2'), 
                  function($join) {
                      $join->on('t1.account', '=', 't2.account')
                           ->on('t1.clear_date', '=', 't2.max_date');
                  })
                ->select('t1.account', 't1.amount', 't1.clear_date as date')
                ->get();

            // error_log("dbbalances 2:");
            // foreach($dbbalances as $bal) error_log(json_encode($bal));

            $balances = [];
            foreach($dbbalances as $balance) {
                $balances[$balance->account] = [$balance->amount, "$", $balance->date, null];
            }
            // error_log("balances:");
            // foreach($balances as $bal) error_log(json_encode($bal));

            foreach($lastBalanceDB as $acct) {
                // error_log("acct: " . $acct);
                // error_log("balances[acct]: " . json_encode($balances[$acct]));
                $retirementDataBalances[$acct] = $balances[$acct];
            }

            // get retirement income
            $dbretincomes = DB::table('transactions')
                ->whereIn('toFrom', $lastDeposit)
                ->whereNull('deleted_at')
                ->select([
                    'toFrom',
                    DB::raw('MAX(trans_date) as max_trans_date'),
                    DB::raw('MAX(amount) as amount'),
                    'total_amt'
                ])
                ->groupBy('toFrom')
                ->get()->toArray();

            // get in useful formate
            $incomes = [];
            foreach($dbretincomes as $acct) {
                $incomes[$acct->toFrom] = [max($acct->amount, $acct->total_amt), "$", $acct->max_trans_date, null];
            }

            // add retirement incomes to retirementData array
            foreach($dbretincomes as $acct) {
                $retirementDataIncomes[$acct->toFrom] = $incomes[$acct->toFrom];
            }

            foreach($lastDeposit as $acct) {
                // error_log("\n\nacct: " . $acct);
                // error_log("retirementData[acct]: " );
                // error_log(json_encode($retirementData[$acct]));
                if(!isset($retirementData[$acct])) $retirementData[$acct] = [0, "$", null];
            }


            // delete old rental records
            $yearMonth = date("y") . date("m");
            foreach($retirementDataRents as $description=>$rentalRcd) {
                $date = substr($description, 12, 4);
                if($date < $yearMonth) {
                    error_log(json_encode($rentalRcd));
                    $result = DB::table('retirementdata')
                        ->where('id', $rentalRcd[3])
                        ->whereNull('deleted_at')
                        ->update([
                            'deleted_at' => now(), 'copied' => 'needupt'
                        ]);
                }
            }

            return [
                $retirementDataAcctNums,
                $retirementDataAssumptions,
                $retirementDataConstants,
                $retirementDataIncomes,
                $retirementDataValues,
                $retirementDataBalances,
                $retirementDataRents
            ];
        }


        // get existing retirement data
        [
            $retirementDataAcctNums,
            $retirementDataAssumptions,
            $retirementDataConstants,
            $retirementDataIncomes,
            $retirementDataValues,
            $retirementDataBalances,
            $retirementDataRents
        ] = getRetirementData();
        
        // return view with input data to calc retirement outlook
        return view('retirementInput', [
            'retirementDataAcctNums' => $retirementDataAcctNums,
            'retirementDataAssumptions' => $retirementDataAssumptions,
            'retirementDataConstants' => $retirementDataConstants,
            'retirementDataIncomes' => $retirementDataIncomes,
            'retirementDataValues' => $retirementDataValues,
            'retirementDataBalances' => $retirementDataBalances,
            'retirementDataRents' => $retirementDataRents
        ]);
    }


    // Prompt for new investment account balances
    public function investmentsindex() {

        function getThreeMonthsAgo() {
            // Get current date/time
            $currentDateTime = new \DateTime();

            // Calculate three months ago
            $threeMonthsAgo = clone $currentDateTime;
            $threeMonthsAgo->modify('-3 months');

            // Set to New York timezone
            $nyTimezone = new \DateTimeZone('America/New_York');
            $threeMonthsAgo->setTimezone($nyTimezone);

            // Format and display the result
            return $threeMonthsAgo->format('Y-m-d');
        }


        // get investment accounts and relavent information
        $threeMonthsAgo = getThreeMonthsAgo();

        // retrieve the recent investment acct info from db
        //  -- most recent first
        $dbinvestments = DB::table('transactions')
            ->where('toFrom', 'Value')
            ->where('lastBalanced', '>=', $threeMonthsAgo)
            ->whereNull('deleted_at')
            ->orderBy('account', 'asc')
            ->orderBy('lastBalanced', 'desc')
            ->get()->toArray();

        // only keep the most recent record for each account
        $investments = [];
        $accountsIncluded = [];
        foreach($dbinvestments as $dbInv) {
            if(!in_array($dbInv->account, $accountsIncluded)) {
                // only keep date part of lastBalanced (not time)
                $dbInv->lastBalanced = substr($dbInv->lastBalanced, 0, 10); // keep yyyy-mm-dd
                // Balance only needs to go to 2 decimal places
                $dbInv->amount = substr($dbInv->amount, 0, -2);

                // add data to array to be displayed on page
                $investments[] = $dbInv;

                // remember that we have info for this account
                // NOTE: data was sorted by most recent first, so the most recent data is saved.
                $accountsIncluded[] = $dbInv->account;
            }
        }

        return view('investmentsindex', ['investments' => $investments]);

    }   // end of function investmentsindex


    // See how much in each bucket and relevant info
    public function buckets() {

        // retrieve bucket info for goal dates in the past
        function getPastGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $pastGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->where('goalDate', '<=', Carbon::now())
                ->whereNull('bucketgoals.deleted_at')
                ->whereNull('transactions.deleted_at')
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();
    
            DB::statement("SET sql_mode=only_full_group_by");
            // only keep 2 decimal places (should have 4)
            foreach($pastGoalDateBuckets as $idx=>$bucket) {
                $pastGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $pastGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $pastGoalDateBuckets;
        }

        // retrieve bucket info for goal dates in the future
        function getFutureGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $futureGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->where('goalDate', '>', Carbon::now())
                ->whereNull('bucketgoals.deleted_at')
                ->whereNull('transactions.deleted_at')
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();

            DB::statement("SET sql_mode=only_full_group_by"); 
            // only keep 2 decimal places (should have 4)
            foreach($futureGoalDateBuckets as $idx=>$bucket) {
                $futureGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $futureGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $futureGoalDateBuckets;
        }

        // retrieve bucket info - no goal dates
        function getNoGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $noGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->whereNull('goalDate')
                ->whereNull('bucketgoals.deleted_at')
                ->whereNull('transactions.deleted_at')
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();
    
            DB::statement("SET sql_mode=only_full_group_by"); 
            // only keep 2 decimal places (should have 4)
            foreach($noGoalDateBuckets as $idx=>$bucket) {
                $noGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $noGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $noGoalDateBuckets;
        }

        // retrieve total bucket info for all goal dates (to compare with transactionsBucketBalance)
        // sum of money in all the buckets
        function getTotalBucketBalance() {

            $totalBucketBalance = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->whereNull('bucketgoals.deleted_at')
                ->whereNull('transactions.deleted_at')
                ->select(
                    DB::raw('SUM(transactions.amount) as balance'),
                )
                ->get()->toArray();

            // only keep 2 decimal places (should have 4)
            $totalBucketBalance = substr($totalBucketBalance[0]->balance, 0, -2);
            
            return $totalBucketBalance;
        }


        // retrieve balance in transactions for buckets (should match totalBucketBalance)
        // sum of all transactions with buckets 
        // NOTE: ONLY Disc Svgs transactions should have buckets, and ALL Disc Svgs transactions should have buckets.
        function getTransactionsBucketBalance() {
            $transactionsBucketBalance = DB::table('transactions')
                ->where("account", "DiscSavings")
                ->whereIn("bucket", [
                    'BigItems', 'College', 'CC', 'Holiday', 'Insurance', 'LTC', 'Misc', 'PropertyTax', 'RetSavings', 'Vacation', 'Water'
                ])
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('SUM(amount) as balance')
                )
                ->get()->toArray();

            // only keep 2 decimal places (should have 4)
            $transactionsBucketBalance = substr($transactionsBucketBalance[0]->balance, 0, -2);
       
            return $transactionsBucketBalance;
        }


        // retrieve bucket info for goal dates in the past
        $pastGoalDateBuckets = getPastGoalDateBuckets();
        
        // retrieve bucket info for goal dates in the future
        $futureGoalDateBuckets = getFutureGoalDateBuckets();

        // retrieve bucket info - no goal dates
        $noGoalDateBuckets = getNoGoalDateBuckets();

        // retrieve total bucket info for all goal dates (to compare with transactionsBucketBalance)
        // sum of money in all the buckets
        $totalBucketBalance = getTotalBucketBalance();
        
        // retrieve balance in transactions for buckets (should match totalBucketBalance)
        // sum of all transactions with buckets 
        // NOTE: ONLY Disc Svgs transactions should have buckets, and ALL Disc Svgs transactions should have buckets.
        $transactionsBucketBalance = getTransactionsBucketBalance();

        // show page with buckets and amounts; shows warning if totals don't match
        return view('buckets', 
            [
                'pastGoalDateBuckets' => $pastGoalDateBuckets, 
                'futureGoalDateBuckets' => $futureGoalDateBuckets, 
                'noGoalDateBuckets' => $noGoalDateBuckets, 
                'totalBucketBalance' => $totalBucketBalance, 
                'transactionsBucketBalance' => $transactionsBucketBalance
            ]
        );

    }   // end of function buckets


    // Init view to move funds between buckets
    public function moveBuckets() {

        // show page with buckets and amounts; shows warning if totals don't match
        return view('moveBuckets');

    }   // end of function moveBuckets


    // Write the transaction records to move the funds between the buckets
    public function moveFundsBetweenBuckets(Request $request) {

        // info needed from request
        $fromBucket = $request->input("fromBucket");
        $toBucket = $request->input("toBucket");
        $moveAmount = $request->input("moveBucketAmount");

        // other calc'd data needed
        $today = date('Y-m-d');
        $stmtDate = substr($today, 2, 2) . "-" . date('M');
        $halfAmt = .5 * $moveAmount;
        $note = "move from " . $fromBucket . " to " . $toBucket;

        // create from transaction
        $fromTransaction = [];
        $fromTransaction['trans_date'] = $today;
        $fromTransaction['clear_date'] = $today;
        $fromTransaction['account'] = "DiscSavings";
        $fromTransaction['toFrom'] = "Move Between Buckets";
        $fromTransaction['amount'] = -$moveAmount;
        $fromTransaction['amtMike'] = -$halfAmt;
        $fromTransaction['amtMaura'] = -$halfAmt;
        $fromTransaction['category'] = "BucketMove";
        $fromTransaction['stmtDate'] = $stmtDate;
        $fromTransaction['total_amt'] = 0;
        $fromTransaction['total_key'] = "move";
        $fromTransaction['bucket'] = $fromBucket;
        $fromTransaction['notes'] = $note;

        // write from transaction
        $newId = DB::table('transactions')->insertGetId($fromTransaction);
        // update total_key with newId
        DB::table('transactions')
            ->where('id', $newId)
            ->whereNull('deleted_at')
            ->update(['total_key' => $newId, 'copied' => 'needupt']);

        // creat to transaction
        $toTransaction = [];
        $toTransaction['trans_date'] = $today;
        $toTransaction['clear_date'] = $today;
        $toTransaction['account'] = "DiscSavings";
        $toTransaction['toFrom'] = "Move Between Buckets";
        $toTransaction['amount'] = $moveAmount;
        $toTransaction['amtMike'] = $halfAmt;
        $toTransaction['amtMaura'] = $halfAmt;
        $toTransaction['category'] = "BucketMove";
        $toTransaction['stmtDate'] = $stmtDate;
        $toTransaction['total_amt'] = 0;
        $toTransaction['total_key'] = $newId;
        $toTransaction['bucket'] = $toBucket;
        $toTransaction['notes'] = "move from " . $fromBucket . " to " . $toBucket;

        // write the "to" transaction
        $result = DB::table('transactions')->insert($toTransaction);

        // reload buckets page
        return redirect()->route('buckets');

    }   // end of function moveFUndsBetweenBuckets


    // get budget categories from categories table
    // IorE:
    //  "I" for income categories
    //  "E" for expense categories
    function getCategories($IorE) {
        $categories = DB::table('categories')
            ->where('ie', '=', $IorE)
            ->whereNull('deleted_at')
            ->select('name')
            ->orderBy('name')
            ->get()->toArray();

            $categories = array_column($categories, "name");

        return $categories;
    }


    public function getBudgetData($year) {
        $budgetRecords = DB::table("budget")
            ->select(["id", "year", "category", "inflationFactor", "january", "february",
                "march", "april", "may", "june", "july", "august", "september", "october",
                "november", "december", "total"])
            ->where("year", $year)
            ->whereNull('deleted_at')
            ->get()->toArray();

        $budgetData = [];
        foreach($budgetRecords as $record) {
            $arrayRecord = (array)$record;
            $budgetData[$arrayRecord['category']] = 
                array_diff_key($arrayRecord, array_flip(['id', 'year' , 'category']));            
        }

        return $budgetData;
    }   // end function getBudgetData


    public function getNotes($typeOfNote, $year) {

        $notes = [];

        $db_notes = DB::table("notes")
            ->where("type_of_note", $typeOfNote)
            ->whereBetween("trans_date", [$year . "-01-01", $year . "-12-31"])
            ->whereNull('deleted_at')
            ->get()->toArray();

        foreach($db_notes as $note) {

            // create new element for this category in notes, if needed
            if(!array_key_exists($note->category, $notes)) {
                $notes[$note->category] = '';
            }

            // create new element for current month, if needed
            // get month
            $noteMonth = date("M", strtotime($note->trans_date));
           
            // Append note to any existing notes
            if($note->amount == null) $amountPhrase = '';
            else $amountPhrase = " ($" . $note->amount . ")";
            $notes[$note->category] .= $noteMonth . ": " . $note->note . $amountPhrase . ".  ";
        }
 
        return $notes;
    }   // end function getNotes


    // see spending transactions
    public function spending() {

        $routeName = Route::currentRouteName();
        $who = str_replace("spending", "", $routeName);
        $who = strtolower($who);
        
        // will need to identify month by number
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get transactions for the current year
        $thisYear = date('Y');

        // make sure who has only the first letter capitalized (to match database)
        $who = strtoupper(substr($who, 0, 1)) . strtolower(substr($who, 1));
      
        // get category
        $category = $who . "Spending";

        // get spending transactions
        // - include household transactions credited to the spending budget
        // - and all transactions in/out of spending savings account
        $spendingTransactions = DB::table("transactions")
            ->select([
                'trans_date',
                DB::raw('YEAR(trans_date) as year'),
                DB::raw('MONTH(trans_date) as month'),
                'clear_date',
                'account',
                'toFrom',
                'amount',
                'total_amt',
                'notes',
                'category'
            ])
            ->where('trans_date', '>=', $thisYear . "-01-01")
            ->whereNull('deleted_at')
            ->where(function ($query) use ($category, $who) {
                $query->where('category', $category)
                      ->whereNull('deleted_at')             // left off here - test this
                        //  Mike has only account "Mike"
                        //  Maura has "MauraSCU" and "MauraDisc"
                      ->orWhere('account', 'like', $who . '%');
            })
            ->orderBy('trans_date')
            ->orderBy('toFrom')
            ->orderBy('amount')
            ->get()
            ->toArray();

        // get spending budget
        $monthlySpendingBudget = DB::table('budget')
            ->where("year", $thisYear)
            ->where("category", $category)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();

        // add budget transaction at beginning of each month.  Use $month to see month change.
        $month = '';

        // keep track of spending left in budget
        $remainingSpendingBudget = 0;

        // new array to send to view (with monthly spending budget lines added)
        $viewTransactions = [];

        // insert lines to add budget; and keep running balance of spending budget remaining
        foreach($spendingTransactions as $key=>$transaction) {

            // get this transaction's month
            $transactionMonthIdx = $transaction->month-1;
            $transactionMonth = $months[$transactionMonthIdx];

            // if month has changed, insert budget transaction
            if($month != $transactionMonth) {
                // change to new month
                $month = $months[$transactionMonthIdx];

                // calc remaining spending budget
                // note: amt is neg in budget table.  Needs to be subtracted to ADD it to the balance.
                $remainingSpendingBudget -= $monthlySpendingBudget[0]->$month;
            
                // build new budget record
                $budgetRecord = (object)[
                    "trans_date" => $thisYear . "-" . str_pad($transaction->month, 2, '0', STR_PAD_LEFT) . "-01",
                    "year" => $thisYear,
                    "month" => $transaction->month,
                    "clear_date" => $thisYear . "-" . str_pad($transaction->month, 2, '0', STR_PAD_LEFT) . "-01",
                    "account" => "budget",
                    "toFrom" => $month . " budget",
                    "amount" => $monthlySpendingBudget[0]->$month,
                    "total_amt" => "",
                    "notes" => $month . " budget",
                    "category" => $category,
                    "remainingSpendingBudget" => $remainingSpendingBudget
                ];
                $viewTransactions[] = $budgetRecord;
            }

            // update spending left, if appropriate
            // and include balance in transaction for view
            if($transaction->category == $category) {

                // update remaining spending budget
                $remainingSpendingBudget += $transaction->amount;

                // append remaining spending budget to current transaction
                $transaction->remainingSpendingBudget = $remainingSpendingBudget;
            } else {
                // append remaining spending budget to current transaction
                // NOTE: value doesn't change if it's to/from Mike/Maura's savings acct (no category)
                $transaction->remainingSpendingBudget = $remainingSpendingBudget;

            }

            // push transaction to viewTransactions
            $viewTransactions[] = $transaction;

        }

        // return view to display spending records
        return view('spending', ['who' => $who, 'spendingTransactions' => $viewTransactions]);
    
    }   // end function spending


    // get car info for trips blade
    public function getCarInfo() {
        // get cars, drivers, and most recent mileage from carcostdetails table
        $DBCarsDriversMileages = DB::table('carcostdetails')
            ->where('key', 'Driver')
            ->whereNull('deleted_at')       // left off here  test this
            ->orWhere('key', 'like', 'Mileage%')
            ->get()->toArray();
            
        // Transform the data
        $carsDriversMileages = [];
        foreach ($DBCarsDriversMileages as $item) {
            // get which car the data is about
            $car = $item->car;

            // is an element initialized for this car?
            if (!isset($carsDriversMileages[$car])) {
                $carsDriversMileages[$car] = ['car' => $car];
            }
            
            // if it's the default driver, capture that
            if ($item->key === 'Driver') {
                $carsDriversMileages[$car]['Driver'] = $item->value;

            // if it's the mileage, handle that
            } elseif (strpos($item->key, 'Mileage') === 0) {
                // get the mileage and the date
                $odomDate = substr($item->key, 7);
                $mileage = (int)$item->value;

                // if this mileage is higher, capture it and the date
                if (!isset($carsDriversMileages[$car]['Mileage']) || $mileage > $carsDriversMileages[$car]['Mileage']) {
                    $carsDriversMileages[$car]['Mileage'] = $mileage;
                    $carsDriversMileages[$car]['OdomDate'] = $odomDate;
                }
            }
        }

        // Convert to array of objects
        $carInfo = array_values($carsDriversMileages);
        // error_log("carInfo:");
        // error_log(json_encode($carInfo));

        return $carInfo;
    }   // end of function getCarInfo


    // get array of trip names
    public function getTripNames() {

        // get an array of all the trip names
        $tripNames = DB::table('trips')
            ->whereNull('deleted_at')
            ->pluck('trip');
        // error_log("tripnames:");
        // foreach($DBtripNames as $tripName) error_log($tripName);   
        
        return $tripNames;
    }

    // calc cost to use car for a trip
    // and write corresponding records to transactions table
    public function trips() {

        // get cars, drivers, and most recent mileage from carcostdetails table
        $carInfo = $this->getCarInfo();

        // get an array of all the trip names
        $tripNames = $this->getTripNames();

        // return view to get info to calc cost to use car for a trip
        return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames]);
    
    }   // end function trips

    
    // See budget info
    public function budget(Request $request) {
        
        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get budget data
        $budgetData = $this->getBudgetData($year);
        
        // budget page
        return view('budget', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'budgetData' => $budgetData
            ]
        );

    }   // end of function budget


    // See budget vs actual info
    public function budgetactuals(Request $request) {
        
        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get budget data
        $budgetData = $this->getBudgetData($year);

        // format with commas and to 2 decimal places
        foreach($budgetData as $category=>$data) {
            $budgetData[$category]['total'] = number_format((float)$data['total'], 2);
            foreach($months as $month) {
                $budgetData[$category][$month] = number_format((float)$data[$month], 2);
            }
        }

        // get actuals data
        [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals] =
            $this->getActualsData($year, $months, $incomeCategories, $expenseCategories);

        // get notes
        $notes = $this->getNotes("b_vs_a", $year);

        // budgetactuals page
        return view('budgetactuals', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'budgetData' => $budgetData,
                'actualIncomeData' => $actualIncomeData,
                'actualExpenseData' => $actualExpenseData,
                'actualIncomeTotals' => $actualIncomeTotals,
                'actualExpenseTotals' => $actualExpenseTotals,
                'actualGrandTotals' => $actualGrandTotals,
                'notes' => $notes
            ]
        );

    }   // end of function budgetactuals


    public function getActualsData($year, $months, $incomeCategories, $expenseCategories) {

        $actualsTransactions = DB::table("transactions")
            ->whereBetween("trans_date", [$year . "-01-01", $year . "-12-31"])
            ->whereNull('deleted_at')
            ->get()->toArray();

        // init actual income and expense data
        $actualIncomeData = [];
        foreach($incomeCategories as $category) {
            $actualIncomeData[$category] = [
                "january" => 0,
                "february" => 0,
                "march" => 0,
                "april" => 0,
                "may" => 0,
                "june" => 0,
                "july" => 0,
                "august" => 0,
                "september" => 0,
                "october" => 0,
                "november" => 0,
                "december" => 0,
                "total" => 0
            ];
        }

        $actualExpenseData = [];
        foreach($expenseCategories as $category) {
            $actualExpenseData[$category] = [
                "january" => 0,
                "february" => 0,
                "march" => 0,
                "april" => 0,
                "may" => 0,
                "june" => 0,
                "july" => 0,
                "august" => 0,
                "september" => 0,
                "october" => 0,
                "november" => 0,
                "december" => 0,
                "total" => 0
            ];
        }

        // sum actuals by month and category
        foreach($actualsTransactions as $actualsTransaction) {
            $thisTransMonth = substr($actualsTransaction->trans_date, 5, 2);
            $thisMonth = $months[(int)$thisTransMonth-1];
            if(in_array($actualsTransaction->category, $incomeCategories)) {
                $actualIncomeData[$actualsTransaction->category][$thisMonth] += $actualsTransaction->amount;
                $actualIncomeData[$actualsTransaction->category]['total'] += $actualsTransaction->amount;
            } else if(in_array($actualsTransaction->category, $expenseCategories)) {
                $actualExpenseData[$actualsTransaction->category][$thisMonth] += $actualsTransaction->amount;                
                $actualExpenseData[$actualsTransaction->category]['total'] += $actualsTransaction->amount;                
            }
        }

        // calc actualIncomeTotals, actualExpenseTotals, actualGrandTotals
        foreach ($actualIncomeData as $category => $catMonths) {
            foreach ($catMonths as $month => $amount) {
                if (!isset($actualIncomeTotals[$month])) $actualIncomeTotals[$month] = 0;
                // remove commas before converting string to float
                $actualIncomeTotals[$month] += (float)(str_replace(',', '', $amount));
            }
        }
        foreach ($actualExpenseData as $category => $catMonths) {
            foreach ($catMonths as $month => $amount) {
                if (!isset($actualExpenseTotals[$month])) {
                    $actualExpenseTotals[$month] = 0;
                }
                // remove commas before converting string to float
                $actualExpenseTotals[$month] += (float)(str_replace(',', '', $amount));
            }
        }

        // calc grand totals for each month
        foreach ($months as $monthIdx => $catMonth) {
            $actualGrandTotals[$catMonth] = $actualIncomeTotals[$catMonth] + $actualExpenseTotals[$catMonth];
            // remove commas before converting string to float
            $actualGrandTotals[$catMonth] = number_format($actualGrandTotals[$catMonth], 2);
        }
        // calc total grand total
        $actualGrandTotals['total'] = $actualIncomeTotals['total'] + $actualExpenseTotals['total'];
        // ...and format
        $actualGrandTotals['total'] = number_format($actualGrandTotals['total'], 2);


        // format all number to 2 decimal places
        foreach($actualIncomeData as $categoryKey=>$category) {
            foreach($months as $month) {
                $actualIncomeData[$categoryKey][$month] = number_format($actualIncomeData[$categoryKey][$month], 2);
            }
            $actualIncomeData[$categoryKey]['total'] = number_format($actualIncomeData[$categoryKey]['total'], 2);
        }

        foreach($actualExpenseData as $categoryKey=>$category) {
            foreach($months as $month) {
                $actualExpenseData[$categoryKey][$month] = number_format($actualExpenseData[$categoryKey][$month], 2);
            }
            $actualExpenseData[$categoryKey]['total'] = number_format($actualExpenseData[$categoryKey]['total'], 2);
        }

        foreach($actualIncomeTotals as $idx=>$total) $actualIncomeTotals[$idx] = number_format($total, 2);

        foreach($actualExpenseTotals as $idx=>$total) $actualExpenseTotals[$idx] = number_format($total, 2);

        return [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals];
    }


    // See actuals info
    public function actuals(Request $request) {

        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get actuals data
        [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals] =
            $this->getActualsData($year, $months, $incomeCategories, $expenseCategories);

        // actuals page
        return view('actuals', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'actualIncomeData' => $actualIncomeData,
                'actualExpenseData' => $actualExpenseData,
                'incomeTotals' => $actualIncomeTotals,
                'expenseTotals' => $actualExpenseTotals,
                'grandTotals' => $actualGrandTotals
            ]
        );

    }   // end of function actuals


    // This was used to eliminate MMSpending transactions, so shouldn't be needed again.
    // transactions table was altered to no longer allow "MMSpending" as a category.
    // split each MMSpending transaction into a MauraSpending and MikeSpending
    public function splitMandM() {

        DB::transaction(function () {
            // get all MMSpending transactions
            $mmTransactions = DB::select('SELECT * FROM transactions WHERE category = "MMSpending"');

            // for each transaction, change the existing one to the MikeCategory
            //      with amount cut in half
            //      amtMike stays the same
            //      amtMaura is 0
            //      category changed to "MikeSpending"
            // and create a new transaction, the same as the original with the following changes:
            //      new id
            //      amount cut in half
            //      amtMaura stays the same (same as amtMike)
            //      amtMike is 0
            //      category changed to "MauraSpending"
            // If there's no total_key, for both the changed original record and the new record...
            //      total_key = id of the original transaction
            //      total_amt = amount of the original transaction
            foreach($mmTransactions as $transaction) {
                try {
                    // save the id to use as the total_key
                    $id = $transaction->id;
                    
                    // keep existing total_amt and total_key
                    // otherwise set to total_amt = original amount, and total_key = original id
                    if($transaction->total_key == null) {
                        $total_key = (string)($id);
                        $total_amt = floatval($transaction->amount);
                    } else {
                        $total_key = $transaction->total_key;
                        $total_amt = $transaction->total_amt;
                    }
                    // handle total_key that includes other transactions
    
                    // change transaction to MikeSpending part & update
                    DB::table('transactions')
                        ->where('id', '=', $id)
                        ->whereNull('deleted_at')
                        ->update([
                            'total_key' => $total_key,
                            'total_amt' => $total_amt,
                            'amount' => DB::raw('CAST(amount / 2 AS FLOAT)'),
                            'amtMaura' => 0,
                            'category' => 'MikeSpending',
                            'copied' => 'needupt'
                        ]);
                
                    
                    // insert new MauraSpending transaction
                    DB::insert('INSERT INTO transactions (
                        trans_date,
                        clear_date,
                        account,
                        toFrom,
                        total_key, 
                        total_amt, 
                        amount, 
                        amtMike,
                        amtMaura, 
                        method,
                        category,
                        tracking,
                        stmtDate,
                        bucket,
                        notes,
                        lastBalanced
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                        $transaction->trans_date,
                        $transaction->clear_date,
                        $transaction->account,
                        $transaction->toFrom,
                        $total_key,
                        $total_amt,
                        ($transaction->amount ?? 0) / 2,    // amount
                        0,                                  // amtMike
                        $transaction->amtMaura,
                        $transaction->method,
                        'MauraSpending',                    // category
                        $transaction->tracking,
                        $transaction->stmtDate,
                        $transaction->bucket,
                        $transaction->notes,
                        $transaction->lastBalanced
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Error splitting M & M Spending: " . $e->getMessage());
                    throw $e;
                }    
            }

        });

        return "Done split M and M.";
    }

    // blade to enter balances for each WF account, so Trad, Roth, and Inh accounts can be treated appropriately
    public function splitIRAs(Request $request) {

        error_log("------------------------------ split IRAS ----------------------------------");
        error_log("Mikes: " . json_encode($request->query("mikes")));
        error_log("Mauras: " . json_encode($request->query("mauras")));

        // get ira account info in a useable form
        $rothAccounts = collect();
        for ($index = 1; $index <= $request->query("numberRoth"); $index++) {
            $rothAccounts[$index] = $request->query("roth_$index");
            // error_log("index: " . $index . ":  roth_<index>: " . $request->query("roth_$index"));
        }
        
        $tradAccounts = collect();
        for ($index = 1; $index <= $request->query("numberTrad"); $index++) {
            $tradAccounts[$index] = $request->query("trad_$index");
            // error_log("index: " . $index . ":  trad_<index>: " . $request->query("trad_$index"));
        }
        
        $inhAccount = $request->query('inh');
        $investmentAcct = $request->query('invest');

        $mikes = $request->query('mikes');
        $mauras = $request->query('mauras');

        // get view with input for each WF account
        return view('splitiras', compact(
            'rothAccounts',
            'tradAccounts',
            'inhAccount',
            'investmentAcct',
            'mikes',
            'mauras'
        ));
    }


    public function addwfparts(Request $request) {

        // init vars
        $rothTotal = 0;
        $tradTotal = 0;

        // calc total of trad ira accts
        $numberTrad = $request->input("numberTrad");
        for($iraIdx = 1; $iraIdx <= $numberTrad; $iraIdx++) {
            $tradBalance = $request->input("Trad{$iraIdx}");
            $tradTotal += $tradBalance;
        }
        // calc total of roth ira accts
        $numberRoth = $request->input("numberRoth");
        for($iraIdx = 1; $iraIdx <= $numberRoth; $iraIdx++) {
            $rothBalance = $request->input("Roth{$iraIdx}");
            $rothTotal += $rothBalance;
        }

        $inhTotal = $request->input("Inh");
        $wfInv = $request->input("wfInv");
        $insertRcds = [
            ['description' =>'WF-IRA-non-taxable-Roth', 'data' => $rothTotal],
            ['description' =>'WF-IRA-Taxable-Trad', 'data' => $tradTotal],
            ['description' =>'WF-Inherited (for LTC)',  'data' => $inhTotal],
            ['description' =>'WF-Inv-Bal', 'data' => $wfInv]
        ];

        $response = DB::table('retirementdata')
            ->upsert(
                $insertRcds,
                ['description'],
                ['data']
            );

        return redirect()->route('retirementInput');

    }

    
    // replace monthly rental income in retirementdata table
    public function saveRents(Request $request) {
        // get input data
        $results = DB::table('retirementdata')
            ->where('description', 'like', 'RentalIncome%')
            ->delete();

        // build insert query
        $monthlyRentals = [];

        foreach([0, 1] as $tenant) {
            foreach([0, 1, 2] as $yearIdx) {
                $year = intval(date("y")) + $yearIdx;
                for($monIdx = 0; $monIdx < 12; $monIdx++) {
                    $rent = $request->input("t" . $tenant . "y" . $yearIdx . "m" . $monIdx);
                    // only save non-0 values
                    if($rent != 0) {
                        $description = 'RentalIncome' . $year . str_pad( strval($monIdx+1), 2, "0", STR_PAD_LEFT) . "-" . $tenant;
                        $newRental = ['description'=>$description, 'type'=>"rent", 'data'=>$rent];
                        $monthlyRentals[] = $newRental;
                    }
                }
            }
        }

        // do the insert
        $results = DB::table("retirementdata")
            ->insert($monthlyRentals);

        // return to retirement data page (hides rental income details)
        return redirect()->route('retirementInput');
    }   // end function saveRents


    // Upload database changes from local to remote tables
    public function syncdbchanges() {
       
        $msg = '';      // to be displayed on page when this is done
    
        // tables to sync
        $tables = [
            'accounts',
            'bucketgoals',
            'budget',
            'carcostdetails',
            'categories',
            'monthlies',
            'notes',
            'retirementdata',
            'tofromaliases',
            'tolls',
            'transactions',
            'trips',
            'uploadmatch'
        ];

        // process one table at a time
        foreach($tables as $table) {

            error_log("Table: " . $table);
            $newMsg = '';

            // for retirementData, delete local input records first
            if($table == 'retirementdata') {
                $response = DB::connection('mysqllocal')
                    ->table('retirementdata')
                    ->where('type', 'inpt')
                    ->delete();
                error_log("Deleted from LOCAL retirementdata table: " . json_encode($response));
            }

            // Get NEW records in REMOTE table
            $newRemoteRecords = DB::table($table)
                ->where('copied', 'new')
                ->get()->toArray();
            error_log(" - Number of copied or new record in remote: " . count($newRemoteRecords));

            // and write them (if any) to the local table
            if(!empty($newRemoteRecords)) {
                // format array
                $recordsToInsert = array_map(function($record) {
                    return (array) $record;
                }, $newRemoteRecords);

                // change 'copied' field to 'yes'
                $mmsCount = 0;
                foreach($recordsToInsert as $idx=>$record) {
                    $recordsToInsert[$idx]['copied'] = 'yes';
                    if($table == 'retirementdata') error_log(" -- mmsCount idx: " . $idx);
                    $mmsCount++;
                }
                if($table == 'retirementdata') error_log("mmsCount: " . $mmsCount);

                // insert records
                $insertResult = DB::connection('mysqllocal')
                    ->table($table)
                    ->insert($recordsToInsert);
                error_log(count($recordsToInsert) . " inserted to Local. " . json_encode($insertResult));

                // update 'copied' field in remote table
                DB::table($table)
                    ->whereIn('id', array_column($newRemoteRecords, 'id'))
                    ->update(['copied' => 'yes']);
                error_log("Updated 'copied' field in REMOTE table.");

                $newMsg .= 'Table <u><span style="font-weight: bold; font-size: 20px;">' . $table . ':</span></u> ' . count($newRemoteRecords) . ' records inserted to REMOTE.<br>';
            }


            // Get NEW records in LOCAL table
            $newLocalRecords = DB::connection('mysqllocal')
                ->table($table)
                ->where('copied', 'new')
                ->get()->toArray();
            error_log(" - Number of copied or new record in local: " . count($newLocalRecords));

            // and write them (if any) to the remote table
            if(!empty($newLocalRecords)) {
                // format array
                $recordsToInsert = array_map(function($record) {
                    return (array) $record;
                }, $newLocalRecords);
                
                // change 'copied' field to 'yes'
                $mmsCount = 0;
                foreach($recordsToInsert as $idx=>$record) {
                    $recordsToInsert[$idx]['copied'] = 'yes';
                    error_log(" -- mmsCount idx: " . $idx);
                    $mmsCount++;
                }
                error_log("mmsCount: " . $mmsCount);
                
                // insert records to REMOTE
                $insertResult = DB::table($table)
                    ->insert($recordsToInsert);
                error_log(count($recordsToInsert) . " inserted to Remote. " . json_encode($insertResult));

                // update 'copied' field in local table
                DB::connection('mysqllocal')
                    ->table($table)
                    ->whereIn('id', array_column($newLocalRecords, 'id'))
                    ->update(['copied' => 'yes']);
                error_log("Updated 'copied' field in LOCAL table.");

                $newMsg .= 'Table <u><span style="font-weight: bold; font-size: 20px;">' . $table . ':</span></u> ' . count($newLocalRecords) . ' records inserted to LOCAL.<br>';
            }

            // Get REMOTE records to UPDATE locally
            $chgdRemoteRecords = DB::table($table)
                ->where('copied', 'needupt')
                ->get()->toArray();

            // Get local records to UPDATE remotely
            $chgdLocalRecords = DB::connection('mysqllocal')
                ->table($table)
                ->where('copied', 'needupt')
                ->get()->toArray();

            // If updates in both places, show differences, and settle manually
            if(count($chgdRemoteRecords) > 0 && count($chgdLocalRecords) > 0) {
                $newMsg .= '<br><br><span style="text-decoration: underline; font-weight: bold;">' . $table . "</span> table: Records need updating in BOTH Remote and Local database.<br>  Reconcile manually.<br>  Here are the changes:<br>";
                $newMsg .= "<br>In LOCAL table " . $table . ":";
                foreach($chgdLocalRecords as $rcd) {
                    $newMsg .= "<br> - " . json_encode($rcd) . "<br>";
                }  
                $newMsg .= "<br>In REMOTE table " . $table . ":";
                foreach($chgdRemoteRecords as $rcd) {
                    $newMsg .= "<br> - " . json_encode($rcd) . "<br>";
                }  

            } else {

                error_log(" - # chgdRemoteRecords: " . count($chgdRemoteRecords));
                error_log(" - # chgdLocalRecords: " . count($chgdLocalRecords));
                // ... left off here sync ...  Coding is done - needs further testing
                // what happens if there is no matching id??


                // Copy remote records to local
                if(count($chgdRemoteRecords) > 0) {
                    error_log(" - have changed remote rcds");
                    // let user see what's being copied.  Can save and switch back if it's not right. (Although not that easily)
                    $newMsg .= "<br><br>" . $table . " Table; Records in the local table are being <u><b>UPDATED</b></u> to match the remote table by id.";
                    foreach($chgdRemoteRecords as $mmsIdx=>$remoteRcd) {
                        // error_log(" -- mmsIdx: " . $mmsIdx);
                        // error_log(" -- ")
                        // set msg to what is being updated in local
                        error_log(" - updating remoteRcd:");
                        // foreach($remoteRcd as $key=>$part) {
                        //     error_log(" -- " . $key . ": " . $part);
                        // }
                        // total in budget table is a formula
                        if($table == 'budget') {
                            unset($remoteRcd->total);
                            error_log(" - modified updating remoteRcd:");
                            // foreach($remoteRcd as $key=>$part) {
                            //     error_log(" -- " . $key . ": " . $part);
                            // }
                        }

                        // get local record with that id
                        error_log(" --- remoteRcd->id: " . $remoteRcd->id);
                        $localRcd = DB::connection('mysqllocal')
                            ->table($table)
                            ->where('id', $remoteRcd->id)
                            ->first();
                        
                        error_log(" - localRcd (to be updated): " . json_encode($localRcd));

                        // add details to message - only show differences
                        if($localRcd == null) {
                            error_log("localRcd is NULL");
                            $newMsg .= "<br><br> NEW remote record written to local:";
                            foreach($remoteRcd as $key=>$rcd) {
                                $newMsg .= "<br> - " . $key . ": " . $rcd;
                            }
                            // change copied to 'yes' before updating remote record
                            $remoteRcd->copied = 'yes';
                            DB::connection('mysqllocal')
                                ->table($table)
                                ->where('id', $remoteRcd->id)
                                ->insert((array)$remoteRcd);                            
                        } else {
                            error_log("localRcd is being updated");
                            $newMsg .= "<br><br>Changes: ";
                            foreach($remoteRcd as $key=>$rRcd) {
                                if($rRcd != $localRcd->{$key}) {
                                    if($rRcd == null) 
                                        $remoteValue = 'null'; 
                                    else $remoteValue = $rRcd;
                                    if($localRcd->{$key} == null) 
                                        $localValue = 'null';
                                    else $localValue = $localRcd->{$key};
                                    $newMsg .= "<br> - " . $key . ": <u><b>" . $remoteValue . "</b></u> (remote) WAS <u><b>" . $localValue . "</b></u> (local).";
                                }
                            }
                            // $newMsg .= "<br><br>to local: " . json_encode($localRcd);
                            // change copied to 'yes' before updating remote record
                            $remoteRcd->copied = 'yes';
                            DB::connection('mysqllocal')
                                ->table($table)
                                ->where('id', $remoteRcd->id)
                                ->update((array)$remoteRcd);                    
                            }

                        // the set copied in remote table to 'yes'
                        DB::table($table)
                            ->where('id', $remoteRcd->id)
                            ->update(['copied' => 'yes']);

                    }
                } else if(count($chgdLocalRecords) > 0) {
                    error_log(" - have changed local rcds");

                    // let user see what's being copied.  Can save and switch back if it's not right. (Although not that easily)
                    $newMsg .= '<br><br><u><span style="font-weight: bold; font-size: 20px;">' . $table . '</span></u> Table; Records in the REMOTE table are being <u><b>UPDATED</b></u> to match the local table by id.';
                    foreach($chgdLocalRecords as $localRcd) {
                        // set msg to what is being updated in REMOTE

                        // get REMOTE record with that id
                        $remoteRcd = table($table)
                            ->where('id', $localRcd->id)
                            ->first();
                        
                        // add details to message
                        $newMsg .= "<br><br>   local:  " . json_encode($localRcd);
                        $newMsg .= "<br><br>to Remote: " . json_encode($remoteRcd);
                        
                        // change copied to 'yes' before updating local record
                        $localRcd->copied = 'yes';
                        DB::table($table)
                            ->where('id', $localRcd->id)
                            ->update((array)$localRcd);

                        // the set copied in local table to 'yes'
                        DB::connection('mysqllocal')
                            ->table($table)
                            ->where('id', $localRcd->id)
                            ->update(['copied' => 'yes']);

                    }
                }
                
                
                // Update record in local table, changing 'copied' to 'yes'
                // WON'T work - matching field might have changed... match records by 'matching' fields - put in db somewhere - need new "tables" table??
                // look for by ID - write to a log file so mistakes can be traced.
                
                // Set 'copied' to 'yes' in REMOTE table
                
                
                
                // Update record in remote table, changing 'copied' to 'yes'
                // WON'T work - matching field might have changed... match records by 'matching' fields - put in db somewhere - need new "tables" table??
                // look for by ID - write to a log file so mistakes can be traced.
                
                // Set 'copied' to 'yes' in LOCAL table
                
            }


            // ******************************************************
            // ******************************************************
            //
            //  old code below
            //
            // ******************************************************
            // ******************************************************

            // DB::table
            // $msg .= 'TABLE: ' . $table->tablename . " (last synced: " . $table->lastdate . ")<br>";
            // $synced = false;  // change to true when table(s) updated

            // // get new records since last upload
            // // error_log("working on table........................................................................ " . $table->tablename);
            // // error_log("--- matchfields: " . $table->matchfields);

            // // change matchfields into an array
            // $matchfields = explode(", ", $table->matchfields);

            // // get new remote records
            // $newRemoteRcds = DB::table($table->tablename)
            //     ->where("created_at", ">", $table->lastdate)
            //     ->whereNull('deleted_at')
            //     ->get()->toArray();

            // $newRemoteIds = array_column($newRemoteRcds, "id");
            // // error_log("newRemoteIds: " . json_encode($newRemoteIds));

            // // get new local records
            // $newLocalRcds = DB::connection('mysqllocal')
            //     ->table($table->tablename)
            //     ->where("created_at", ">", $table->lastdate)
            //     ->whereNull('deleted_at')
            //     ->get()->toArray();
            // $newLocalIds = array_column($newLocalRcds, "id");
            // // error_log("newLocalIds: " . json_encode($newLocalIds));

            // // get updated (but not new) remote records
            // $updatedRemoteRcds = DB::table($table->tablename)
            //     ->where("updated_at", ">", $table->lastdate)
            //     ->whereNotIn("id", $newRemoteIds)
            //     ->whereNull('deleted_at')
            //     ->get()->toArray();
            // $updatedRemoteIds = array_column($updatedRemoteRcds, "id");
            // // error_log("updatedRemoteIds: " . json_encode($updatedRemoteIds));

            // // get updated (but not new) local records
            // $updatedLocalRcds = DB::connection('mysqllocal')
            //     ->table($table->tablename)
            //     ->where("updated_at", ">", $table->lastdate)
            //     ->whereNotIn("id", $newLocalIds)
            //     ->whereNull('deleted_at')
            //     ->get()->toArray();
            // $updatedLocalIds = array_column($updatedLocalRcds, "id");
            // // error_log("updatedLocalIds: " . json_encode($updatedLocalIds));

            // // get deleted records from remote table since last sync
            // $deletedRemoteRcds = DB::table($table->tablename)
            //     ->where("deleted_at", ">", $table->lastdate)
            //     ->get()->toArray();
            // $deletedRemoteIds = array_column($deletedRemoteRcds, "id");
            // // error_log("deletedRemoteIds: " . json_encode($deletedRemoteIds));

            // // get deleted records from local table since last sync
            // $deletedLocalRcds = DB::connection('mysqllocal')
            //     ->table($table->tablename)
            //     ->where("deleted_at", ">", $table->lastdate)
            //     ->get()->toArray();
            // $deletedLocalIds = array_column($deletedLocalRcds, "id");
            // // error_log("deletedLocalIds: " . json_encode($deletedLocalIds));

            // // if no recent changes to local database, copy changes from remote to local
            // if( count($newLocalRcds) == 0 && count($updatedLocalRcds) == 0 && count($deletedLocalRcds) == 00 ) {

            //     // insert new records
            //     $insertRcds = [];
            //     $insertIds = [];    // delete old records before inserting new ones

            //     foreach($newRemoteRcds as $newRcd) {
            //         $insertRcd = [];
            //         foreach ($newRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //     }

            //     // NOTE: There are no records to update or delete in the remote tables,
            //     // so insert those as well.
            //     foreach($updatedRemoteRcds as $updatedRcd) {
            //         $insertRcd = [];
            //         foreach ($updatedRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //     }
            //     foreach($deletedRemoteRcds as $deletedRcd) {
            //         $insertRcd = [];
            //         foreach ($deletedRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //     }

            //     if(count($insertRcds) == 0) {
            //         $msg .= "No new changes found.  No records written.<br>";
            //     } else {
            //         // foreach($insertRcds as $rcd) error_log(json_encode($rcd));
            //         // foreach($insertIds as $rcd) error_log(json_encode($rcd));
            //         // delete old record before inserting the new one
            //         $result = DB::connection('mysqllocal')
            //             ->table($table->tablename)
            //             ->whereIn('id', $insertIds)
            //             ->delete();
            //         // error_log("result of deleting insertIds: " . json_encode($result));

            //         $result = DB::connection('mysqllocal')
            //             ->table($table->tablename)
            //             ->insert($insertRcds);
            //         if($result) $synced = true;
            //         // error_log("result of inserting newRemoteRcds: " . $result);

            //         $msg .= count($insertRcds) . " records written to LOCAL table " . $table->tablename . "<br>";
            //     }

            // }

            // // if no recent changes to remote database, copy changes from local to remote
            // else if( count($newRemoteRcds) == 0 && count($updatedRemoteRcds) == 0 && count($deletedRemoteRcds) == 00 ) {

            //     // insert new records
            //     $insertRcds = [];
            //     $insertIds = [];    // delete old records before inserting new ones

            //     foreach($newLocalRcds as $newRcd) {
            //         $insertRcd = [];
            //         foreach ($newRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //     }

            //     // NOTE: There are no records to update or delete in the Local tables,
            //     // so insert those as well.
            //     foreach($updatedLocalRcds as $updatedRcd) {
            //         $insertRcd = [];
            //         foreach ($updatedRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //     }
            //     foreach($deletedLocalRcds as $deletedRcd) {
            //         $insertRcd = [];
            //         foreach ($deletedRcd as $key => $value) {
            //             $insertRcd[$key] = $value;
            //         }
            //         $insertRcds[] = $insertRcd;
            //         $insertIds[] = $insertRcd['id'];
            //         // error_log("**** -- id: " . $insertRcd['id'] . ";  insertIds: " . json_encode($insertIds));
            //     }

            //     // foreach($insertRcds as $rcd) error_log(json_encode($rcd));
            //     // delete old record before inserting the new one
            //     $result = DB::table($table->tablename)
            //         ->whereIn('id', $insertIds)
            //         ->delete();
            //     // error_log("ids deleted: " . json_encode($insertIds));
            //     // error_log("result of deleting newLocalRcds: " . json_encode($result));

            //     $result = DB::table($table->tablename)
            //         ->insert($insertRcds);
            //     if($result) $synced = true;

            //     // error_log("result of inserting newLocalRcds: " . $result);
            //     $msg .= count($insertRcds) . " records written to REMOTE table " . $table->tablename . "<br>";

            // } else {
            //     // show screen with differences found, to be reconciled manually (for now)
            //     $msg .= "NO RECORDS WRITTEN.  Discrepancies found: <br>";

            //     // recent new in remote table
            //     if(count($newRemoteRcds) > 0) $msg .= "<br>" . count($newRemoteRcds) . " recent 'created_at' in REMOTE table for ids:<br>";
            //     foreach($newRemoteRcds as $newrcd) {
            //         $msg .= "-- " . $newrcd->id . "<br>";
            //     }
                
            //     // recent new in local table
            //     if(count($newLocalRcds) > 0) $msg .= "<br>" . count($newLocalRcds) . " recent 'created_at' in LOCAL table for ids:<br>";
            //     foreach($newLocalRcds as $newrcd) {
            //         $msg .= "-- " . $newrcd->id . "<br>";
            //     }
                
            //     // recent updated in remote table
            //     if(count($updatedRemoteRcds) > 0) $msg .= "<br>" . count($updatedRemoteRcds) . " recent 'updated_at' in REMOTE table for ids:<br>";
            //     foreach($updatedRemoteRcds as $updatedrcd) {
            //         $msg .= "-- " . $updatedrcd->id . "<br>";
            //     }
                
            //     // recent updated in local table
            //     if(count($updatedLocalRcds) > 0) $msg .= "<br>" . count($updatedLocalRcds) . " recent 'updated_at' in LOCAL table for ids:<br>";
            //     foreach($updatedLocalRcds as $updatedrcd) {
            //         $msg .= "-- " . $updatedrcd->id . "<br>";
            //     }

            //     // recent deleted in remote table
            //     if(count($deletedRemoteRcds) > 0) $msg .= "<br>" . count($deletedRemoteRcds) . " recent 'deleted_at' in REMOTE table for ids:<br>";
            //     foreach($deletedRemoteRcds as $deletedrcd) {
            //         $msg .= "-- " . $deletedrcd->id . "<br>";
            //     }
                
            //     // recent deleted in local table
            //     if(count($deletedLocalRcds) > 0) $msg .= "<br>" . count($deletedLocalRcds) . " recent 'deleted_at' in LOCAL table for ids:<br>";
            //     foreach($deletedLocalRcds as $deletedrcd) {
            //         $msg .= "-- " . $deletedrcd->id . "<br>";
            //     }

            // }

            if($newMsg != '') {
                $msg .= $newMsg . '<br>---------------------------------------<br>';    // to delimit between tables
            }

            // update date in lastsync table
            // if($synced) {
            //     DB::table('lastsync')
            //         ->where('tablename', $table->tablename)
            //         ->update(['lastdate' => now(), 'copied' => 'needupt']);
            // }

        }
   
        if($msg == '') $msg = "No changes made.";
        return $msg;
    }


    // add arrays by position to get a new array which is the sum of each position
    public function addArraysByPosition(...$arrays): array {
        // Get length of first array
        $length = count($arrays[0]);

        // Verify all arrays have same length
        foreach ($arrays as $array) {
            if (count($array) !== $length) {
                throw new ValueError("All arrays must have the same length");
            }
        }
        
        // Initialize result array with zeros
        $result = array_fill(0, $length, 0);
        
        // Add arrays position by position
        foreach ($arrays as $array) {
            for ($i = 0; $i < $length; $i++) {
                $result[$i] += $array[$i];
            }
        }
        
        return $result;
    }


    // retirement forecast view
    public function retirementForecast() {


        // get initial balances for accounts as of the first of the current month
        function initialBalances($date) {
            // group accounts
            $spendingAccts = ['Savings', 'Checking'];
            $invAccts = ['WF-Inv-Bal', 'EJ'];
            $retTaxAccts = ['WF-IRA-Taxable-Trad', 'RetirementDisc', 'TIAA'];
            $retNonTaxAccts = ['WF-IRA-non-taxable-Roth'];
            $LTCAccts = ['WF-Inherited', 'LTC-Disc'];

            // get spending accounts balance from retirementdata table
            $beginOfThisMonthSpendingBal = DB::table("retirementdata") 
                ->whereIn("description", $spendingAccts)
                ->where("type", "inpt")
                ->whereNull("deleted_at")
                ->sum("data");
            
            // get investement accounts balance from retirementdata table
            $invAcctsBal = DB::table("retirementdata") 
                ->whereIn("description", $invAccts)
                ->where("type", "inpt")
                ->whereNull("deleted_at")
                ->sum("data");

            // get balance of taxable retirement accts
            $retTaxAcctsBal = DB::table("retirementdata") 
                ->whereIn("description", $retTaxAccts)
                ->where("type", "inpt")
                ->whereNull("deleted_at")
                ->sum("data");

            // get LTC balance
            // data to get LTC in WF Trad (taxable) IRAs
            $ltcDataDB = DB::table('retirementdata') 
                ->select('description', 'data', 'type')
                ->whereIn('type', ['inpt', 'con', 'assm']) 
                ->whereNull('deleted_at') 
                ->where(function ($q) { 
                    $q->where('description', 'like', 'LTCinWF%') 
                    ->orWhere('description', '=', 'LTCInvGrowth'); 
                }) 
                ->get()->toArray();
            // LTC balances
            $ltcAcctSum = DB::table('retirementdata')
                ->whereIn("description", $LTCAccts)
                ->where("type", "inpt")
                ->whereNull("deleted_at")
                ->sum("data");
            error_log("ltcAcctSum: " . $ltcAcctSum);

            $LTCinWF = [];
            $LTCinWFdate = [];
            // subtract LTC balance from WF
            // left off here - REMEMBER to add it to LTC balance
            foreach($ltcDataDB as $data) {
                switch($data->description) {
                    case substr($data->description, 0, 11) == 'LTCinWFdate':
                        error_log("LTCinWFdate #: " . $data->description . " (" . substr($data->description, 11) . ") data: " . $data->data);
                        $LTCinWFdate[substr($data->description, 11)] = $data->data;
                        break;
                    case $data->description == 'LTCInvGrowth':
                        $LTCInvGrowth = $data->data;
                        break;
                    case substr($data->description, 0, 7) == 'LTCinWF':
                        error_log("LTCinWF #: " . $data->description . " (" . substr($data->description, 7) . ") data: " . $data->data);
                        $LTCinWF[substr($data->description, 7)] = $data->data;
                        break;
                }
            }

            // get init LTC bal
            // first get what's in WF
            $initLTCBal = 0;
            error_log("LTC... LTCinvGrowth: " . $LTCInvGrowth . ";");

            $idx = 1;
            while(isset($LTCinWF[$idx])) {
                error_log(" - LTCinWF[" . $idx . "]: " . $LTCinWF[$idx] . "; LTCinWFdate[" . $idx . "]: " . $LTCinWFdate[$idx]);
                $initLTCBal += $LTCinWF[$idx];

                $idx++;
            }
            // then add other LTC accts
            error_log("initLTCBal: " . $initLTCBal);

            // subract LTC princ that's in WF from retirement balance
            error_log("retTaxAcctsBal (tot): " . $retTaxAcctsBal);
            $retTaxAcctsBal -= $initLTCBal;
            error_log("retTaxAcctsBal (-LTC): " . $retTaxAcctsBal);

            error_log("ltcAcctSum: " . $ltcAcctSum);
            $initLTCBal += $ltcAcctSum;
            error_log("initLTCBal: " . $initLTCBal);

            //      interest rate
            $rate = $LTCInvGrowth/100;
            //      interest since date - get number of days elapsed
            $firstOfThisMonth = date("m/1/Y");
            $firstOfThisMonth = new DateTime($firstOfThisMonth);
            $totInterest = 0;
            $idx = 1;
            while(isset($LTCinWF[$idx])) {
                $initDate = substr($LTCinWFdate[$idx], 0, 2) . "/" . substr($LTCinWFdate[$idx], 2, 2) . "/" . '20' . substr($LTCinWFdate[$idx], 4, 2);
                $initDate = new DateTime($initDate);
                error_log("initDate: " . $initDate->format('y-m-d'));
                error_log("firstOfThisMonth: " . $firstOfThisMonth->format('y-m-d'));
                error_log("date diff: " . date_diff($initDate, $firstOfThisMonth)->format('%a'));
                $elapsedDays = date_diff($initDate, $firstOfThisMonth)->format('%a');
                error_log("elapsed days " . $idx . ": " . $elapsedDays);
                // interest
                $interest = round($LTCinWF[$idx] * $rate * ($elapsedDays/365), 2);
                error_log("interest " . $idx . ": " . $interest);
                $totInterest += $interest;

                $idx++;
            }
            error_log("total interest: " . $totInterest);
            $initLTCBal += $totInterest;
            error_log("FINAL initLTCBal: " . $initLTCBal);

            // get balance of non-taxable (Roth) accts
            $retNonTaxAcctsBal = DB::table("retirementdata") 
                ->whereIn("description", $retNonTaxAccts)
                ->where("type", "inpt")
                ->whereNull("deleted_at")
                ->sum("data");

            $spendingAccts = implode(", ", $spendingAccts);
            $invAccts = implode(", ", $invAccts);
            $retTaxAccts = implode(", ", $retTaxAccts);
            $retNonTaxAccts = implode(", ", $retNonTaxAccts);
            return [$beginOfThisMonthSpendingBal, $invAcctsBal, $retTaxAcctsBal, $retNonTaxAcctsBal, $spendingAccts, $invAccts, $retTaxAccts, $retNonTaxAccts, $initLTCBal];
        }   // end of function initialBalances


        // get income expected for the rest of this year as of the first of this month
        function thisYearIncome($date, $twoDigitYear) {

            $remainingIncomeThisYear = [];
            $inputIncomes = ["NH Retirement", "MMS-IBM-Retirement", "SSMaura", "RentalIncome".$twoDigitYear];
            $inputIncomesDescriptions = ["NHRetirement", "MauraIBM", "MauraSS65", "MauraSS67", "RentalIncome".$twoDigitYear];
            $otherRetirementDataNeeded = ["GBLimoForExpenses", "EndGBJob", "EndTownJob", "EndRentalJob", "MauraIBMStart", "MauraSSStart" ];
            $dataBaseIncomesLine = ["Town of Durham", "GB Limo", "Mike IBM", "Mike SS", "Tax Retire", "Non-Tax Retire", "Investment Growth"];
            $dataBaseIncomestoFrom = ["Town of Durham", "Great Bay Limo", "MTS-IBM-Retirement", "SSMike"];

            $queryDescriptions = array_merge($inputIncomesDescriptions, $otherRetirementDataNeeded);
            $retirementDataInfoDB = DB::table('retirementdata')
                ->select("data", "description", "type")
                ->whereIn("description", $queryDescriptions)
                ->whereNull("deleted_at")
                ->get()->toArray();

            $retirementDataInfo = [];
            $retirementDataHaveInputValue = []; // if element is true, have the input value; if exists, have other value; if undefined, don't have a value
            foreach($retirementDataInfoDB as $idx=>$retirementDatum) {
                // error_log("---*" . $idx . ": " . json_encode($retirementDatum));

                // if no data for this description found yet, note if it's "inpt" type and save the value
                if(!isset($retirementDataHaveInputValue[$retirementDatum->description])) {
                    // error_log("init " . $retirementDatum->description);
                    $retirementDataHaveInputValue[$idx] = ($retirementDatum->type == 'inpt') ? true : false;
                    $retirementDataInfo[$retirementDatum->description] = $retirementDatum->data;

                // if there is a data value for this description, only change it if the new value is type 'inpt'
                } else {
                    if($retirementDatum->type == 'inpt') {
                        // error_log("update " . $retirementDatum->description);
                        $retirementDataHaveInputValue[$idx] = true;
                        $retirementDataInfo[$retirementDatum->description] = $retirementDatum->data;
                    }
                    else error_log("ignore " . $retirementDatum->description);
                }
                // error_log("retirementDataHaveInputValue: " );
                // error_log(json_encode($retirementDataHaveInputValue));
                // error_log("retirementDataInfo:");
                // foreach($retirementDataInfo as $thiss=>$thing) {
                //     error_log(" -- " . $thiss . ": " . $thing);
                // }
                // error_log(json_encode($retirementDataInfo));
                // error_log("retirementDataHaveInputValue[" . $retirementDatum->description . "]: " . $retirementDataHaveInputValue[$retirementDatum->description]);
                // error_log("retirementDataInfo[" . $retirementDatum->description . "]: " . $retirementDataInfo['description']);
            }

            // error_log("retirementDataInfo:");
            // foreach($retirementDataInfo as $desc=>$data) {
            //     error_log("--- " . $desc . " ----");
            //     error_log(json_encode($data));
            //     error_log("===================================");
            // }

            // error_log(" --- ");
            foreach($inputIncomesDescriptions as $income) {
                error_log("test 3 -- income: " . $income);
                // error_log("retirementDataInfo: " . json_encode($retirementDataInfo));
                error_log("retirementDataInfo[".$income."]: " . $retirementDataInfo[$income]);
                $remainingIncomeThisYear[$income] = $retirementDataInfo[$income];
                // error_log(json_encode($remainingIncomeThisYear));
                // error_log("------------------");
            }

            return $remainingIncomeThisYear;
        }   // end of function thisYearIncome


        // Town of Durham income predictions based on hourly wage and hours worked per week.
        function getTownOfDurhamIncomes($date) {
            function getWeeksRemainingInYear($date): int
            {
                // Convert input to DateTime object if it's a string
                $startDate = ($date instanceof \DateTime) ? clone $date : new \DateTime($date);

                // Get last day of the year
                $lastDayOfYear = new \DateTime($startDate->format('Y') . '-12-31');
                
                // Calculate total days remaining
                $interval = $startDate->diff($lastDayOfYear);
                $totalDays = $interval->days;
                
                // convert days to weeks; Don't include partial weeks
                return floor($totalDays / 7);
            }

            // init array to return
            $townOfDurhamIncomes = [];

            // get data from database
            $townData = DB::table('retirementdata')
                ->select('description', 'data')
                ->where('type', 'inpt')
                ->whereIn('description', ['EndTownJob', 'TownOfDurhamHourly', 'TownOfDurhamHrsPerWeek', 'SSCOLA'])
                ->whereNull('deleted_at')
                ->orderBy('description')
                ->get()->toArray();

            // EndTownJob, TownOfDurhamHourly, TownOfDurhamHrsPerWeek and SSCOLA are returned in alphabetical order
            // get EndTownJob date in usable form (yyyy-mm-dd)
            $dateEndTownJob = '20' . substr($townData[0]->data, 4) . '-' . substr($townData[0]->data, 0, 2) . '-' . substr($townData[0]->data, 2, 2);

            // assume yearly raises are 1% above cola
            $COLA = $townData[1]->data; // ss cola
            $raise = 1+$COLA/100;         // assume raise is COLA

            // get hourly rate and hours per week
            $hourlyRate = $townData[2]->data;
            $hoursPerWeek = $townData[3]->data;

            // calc income for rest of year; and set to first element of income array
            $thisYearRemainingIncome = getWeeksRemainingInYear($date) * $hoursPerWeek * $hourlyRate;
            $townOfDurhamIncomes[] = $thisYearRemainingIncome;

            // subsequent years will be based on full year, so calc full year pay
            $prevYearIncome = 52 * $hoursPerWeek * $hourlyRate;

            // increase pay by raise for subsequent years, until EndTownJob date (assumed end of year)
            for($year = ((int)substr($date, 0, 4))+1; $year <= 2062; $year++) {
                if(($year . '-12-31') <= $dateEndTownJob) {
                    $thisYearIncome = $prevYearIncome * $raise;
                } else {
                    $thisYearIncome = 0;
                }

                // push new income to end of array
                $townOfDurhamIncomes[] = $thisYearIncome;

                // new income becomes base for next year
                $prevYearIncome = $thisYearIncome;
            }

            return [$townOfDurhamIncomes, $raise, $COLA];  // can use raise & COLA for other predictions
        }   // end of function getTownOfDurhamIncomes


        // GB Limo income predicitons based on SS income limits & pay so far
        function getGBLimoIncomes($date, $raise) {

            // this will be populated with the expected GBLimo income for each year forecasted
            $gbLimoIncomes = [];

            // get EndGBJob (when GB Limo job ends) from retirementdata table
            $gbDataDB = DB::table('retirementdata')
                ->select('description', 'data', 'type')
                ->where('description', 'EndGBJob')
                ->whereNull('deleted_at')
                ->orderBy('description')
                ->get()->toArray();
            
            // keep 'inpt' type if it exists
            $gbData = [];
            foreach($gbDataDB as $idx=>$datum) {

                // keep if no value yet, keep it
                if(!isset($gbData[$datum->description])) {
                    $gbData[$datum->description] = $datum->data;

                // change value if type is 'inpt'
                } else if($datum->type == 'inpt') {
                    $gbData[$datum->description] = $datum->data;
                }
            }

            // get date variables needed
            $year = substr($date, 0, 4);
            $currMonthNum = substr($date, 5, 1);
            $firstOfYear = $year . '-01-01';
            $firstOfMonth = $year . '-' . $currMonthNum . '-01';
            $dateEndGBJob = '20' . substr($gbData['EndGBJob'], 4) . '-' . substr($gbData['EndGBJob'], 0, 2) . '-' . substr($gbData['EndGBJob'], 2, 2);

            // get GB income up to the first of this month for this year
            $thisYearIncome = DB::table('transactions')
                ->where('toFrom', 'Great Bay Limo')
                ->where('category', 'IncomeMisc')
                ->whereBetween('trans_date', [$firstOfYear, $currMonthNum])
                ->sum('amount');

            // get the budget for IncomeMisc (should only be GBLimo) for this year
            $thisYearBudgetDB = DB::table('budget')
                ->where('category', 'IncomeMisc')
                ->where('year', $year)
                ->first();      // should only be one record that matches

            // remaining budget is full year - what's been earned so far
            $thisYearRemainingBudget = $thisYearBudgetDB->total - $thisYearIncome;

            // set income for the rest of this year to first element of income array
            $gbLimoIncomes[] = $thisYearRemainingBudget;

            // subsequent years will be based on full year, so set prev year to this year's full income
            $prevYearIncome = $thisYearRemainingBudget;         

            // increase pay by raise for subsequent years, until EndTownJob date (assumed end of year)
            for($year = ((int)substr($date, 0, 4))+1; $year <= 2062; $year++) {
                if(($year . '-12-31') <= $dateEndGBJob) {
                    $thisYearIncome = $prevYearIncome * $raise;
                } else {
                    $thisYearIncome = 0;
                }

                // push new income to end of array
                $gbLimoIncomes[] = $thisYearIncome;

                // new income becomes base for next year
                $prevYearIncome = $thisYearIncome;
            }

            return $gbLimoIncomes;

        }   // end of function getGBLimoIncome


        // rental income predicitons
        function getRentalIncomes($date, $raise) {

            $rentalIncomes = [];

            // get rental incomes from retirementdata
            $rentalData = DB::table('retirementdata')
                ->select('description', 'data')
                ->where('type', 'inpt')
                ->where('description', 'like', '%rental%')
                ->whereNull('deleted_at')
                ->orderBy('description')
                ->get()->toArray();
            
            // EndRentalJob, RentalIncomeYY0, RentalIncomeYY1, RentalIncomeYY3 (in 2025, YY0 is 25, YY1 is 26, YY2 is 27) are returned in ABC order
            $dateEndRentalJob = '20' . substr($rentalData[0]->data, 4) . '-' . substr($rentalData[0]->data, 0, 2) . '-' . substr($rentalData[0]->data, 2, 2);
            $rentalIncomeY1 = $rentalData[1]->data;
            $rentalIncomeY2 = $rentalData[2]->data;
            $rentalIncomeY3 = $rentalData[3]->data;

            // Get RentalIncomeY1 first
            $idx = 1;

            // increase pay by raise for subsequent years, until EndTownJob date (assumed end of year)
            for($year = ((int)substr($date, 0, 4)); $year <= 2062; $year++) {
                if(($year . '-12-31') <= $dateEndRentalJob) {
                    // use projected rental incomes in input; then just increase by $raise until done renting rooms
                    if($idx == 1) $thisYearIncome = $rentalIncomeY1;
                    else if($idx == 2) $thisYearIncome = $rentalIncomeY2;
                    else if($idx == 3) $thisYearIncome = $rentalIncomeY3;
                    else $thisYearIncome = $prevYearIncome * $raise;
                    $prevYearIncome = $thisYearIncome;
                } else {  // no longer renting rooms
                    $thisYearIncome = 0;
                }
                
                $idx++;  // get next rental income year

                // push new income to end of array
                $rentalIncomes[] = $thisYearIncome;

                // new income becomes base for next year
                $prevYearIncome = $thisYearIncome;
            }

            return $rentalIncomes;

        }   // end of function getRentalIncomes
        
        
        // fixed income predicitons (doens't change)
        function getFixedIncomes($date, $incomeType) {

            $fixedIncomes = [];

            // get rental incomes from retirementdata
            $fixedIncomeDB = DB::table('retirementdata')
                ->select('description', 'data')
                ->where('type', 'inpt')
                ->whereIn('description', [$incomeType, $incomeType . 'Start'])
                ->whereNull('deleted_at')
                ->orderBy('description')
                ->get();
            $fixedIncome = $fixedIncomeDB->pluck('data');
            $fixedIncome = $fixedIncome[0];

            if(count($fixedIncomeDB) == 2) {
                $startDate = $fixedIncomeDB[1]->data;   // date to begin receiving income
                $startYear = (int)('20' . substr($startDate, 4, 2));    // year to begin 
                // format year
                $startDate = '20' . substr($startDate, 4, 2) . '-' . substr($startDate, 0, 2) . '-' . substr($startDate, 2, 2);
            } else {
                // this year
                $startYear = (int)date('Y');
                // old date
                $startDate = '2024-01-01';
            }

            $thisMonth = substr($date, 5, 2);
            $monthsLeftThisYear = 12 - $thisMonth + 1;
            // error_log("IncomeType: " . $incomeType . "\n - this year: " . date("y") . "\n - date: " . substr($date, 2, 2) . "\n - months left: " . $monthsLeftThisYear . "\n - startYear: " . $startYear . "\n - startDate: " . $startDate);

            // set pay the same for each year
            $firstYear = true;  // may need to pro-rate first year
            for($year = ((int)substr($date, 0, 4)); $year <= 2062; $year++) {
                // push new income to end of array
                if($startYear <= $year) {
                    if($firstYear && $startDate <= $date) {  
                        // if benefit has already started, income for rest of year
                        $fixedIncomes[] = $fixedIncome * $monthsLeftThisYear;
                        $firstYear = false; // don't do this in later years
                    } else if ($firstYear && $startDate > $date) {
                        // if benefit starts later this year, only months receiving it
                        $benefitMonths = 12 - substr($startDate, 5, 2) + 1;
                        $fixedIncomes[] = $fixedIncome * $benefitMonths;
                        $firstYear = false; // don't do this in later years
                    } else {
                        // full 12 months
                        $fixedIncomes[] = $fixedIncome * 12;
                    }
                } else {
                    // not receiving it yet
                    $fixedIncomes[] = 0;
                }
            }

            return $fixedIncomes;

        }   // end of function getFixedIncomes
        
        
        // SS income predicitons
        function getSSIncomes($date, $COLA, $who) {

            // holds SS income estimate for each year 
            $SSIncomes = [];

            // get SS income date from retirementdata
            if($who == 'Mike') $ssDataWhereIn = ['SSMike'];
            else if ($who == 'Maura') $ssDataWhereIn = ['MauraSSStart', 'MauraSS65', 'MauraSS67', 'SSMaura'];
            else $ssDataWhereIn = [];

            $SSData = DB::table('retirementdata')
                ->select('description', 'data')
                ->where('type', 'inpt')
                ->whereIn('description', $ssDataWhereIn)
                ->whereNull('deleted_at')
                ->orderBy('description')
                ->get()->toArray();

            $today = date('Y-m-d');     // today in yyyy-mm-dd format
            $thisYear = date('Y');      // this year - yyyy

            if($who == 'Mike') {
                // only SSMike returned
                $start = '2024-01-01'; // old date - already started
                $startYear = '2024';    // old - already started
                $initIncome = $SSData[0]->data * 12;    // full year benefits
            } else if($who == 'Maura') {
                // MauraSS65, MauraSS67, MauraSSStart, SSMaura (current amt if it's already started) returned in ABC order
                $start = $SSData[2]->data;  // get start date
                // format start date
                $start = '20' . substr($start, 4, 2) . '-' . substr($start, 0, 2) . '-' . substr($start, 2, 2);
                // just the year (yyyy)
                $startYear = substr($start, 0, 4);

                if($start <= $today) {
                    // already started - full year benefits as inputted 
                    $initIncome = $SSData[3]->data * 12;
                } else if($startYear == '2027') {   // Maura turns 65 in 2027
                    // use 2027 income as entered
                    $initIncome = $SSData[0]->data * 12;
                } else if($startYear == '2029') { // Maura turns 67 in 20029
                    // use 2029 income as entered
                    $initIncome = $SSData[1]->data * 12;
                } else {    // not a valid choice
                    error_log("ERROR:  Shouldn't have a start date for Maura SS that is not 2027 or 2029, or in the past");
                    $initIncome = 0;
                }
            }

            // start when SS starts for that person & increase pay by COLA for subsequent years
            // have benefits started yet?
            if($start < $today) $isSSstarted = true;
            else $isSSstarted = false;

            // if it's starting this year...
            if($startYear == $thisYear) {
                // First year benefits Apr - Dec (9 months)...so no more than that
                $monthsLeftThisYear = min(9, 12 - (int)date('m') + 1);
                // set the first year's benefits
                $SSIncomes[] = $initIncome * $monthsLeftThisYear;
                // prev year should be full year (COLA increase based on full year)
                $prevYearIncome = $initIncome;
                $isSSstarted = true;

            // if benefits already started...
            } elseif ($startYear < $thisYear) {
                $monthsLeftThisYear = 12 - (int)date('m') + 1;
                $SSIncomes[] = $initIncome * $monthsLeftThisYear/12;
                $prevYearIncome = $initIncome;  // future benefits based on full year income
                $isSSstarted = true;

            // or if not started/starting this year
            } else {
                $SSIncomes[] = 0;
                $prevYearIncome = 0;
            }

            // calc each subsequent year startign with next year
            for($year = $thisYear+1; $year <= 2062; $year++) {
                // if benefits have not yet started
                if(!$isSSstarted) {
                    // if benefits begin this year
                    if($startYear == $year) {
                        $benefitMonths = 9; // April through Dec
                        // only get a parial year's benefits
                        $thisYearIncome = $initIncome * $benefitMonths/12;
                        // previous year used to increase by COLA, so needs to be full year
                        $prevYearIncome = $initIncome;
                        $isSSstarted = true;

                    // if benefits have already started (this shouldn't run)
                    } else if($startYear < $year) {
                        $thisYearIncome = $initIncome;
                        $prevYearIncome = $thisYearIncome;
                        $isSSstarted = true;

                    // if benefits have not yet started
                    } else {
                        $thisYearIncome = 0;
                        $prevYearIncome = $thisYearIncome;
                    }

                // if benefits have started and are continuing
                } else {
                    $thisYearIncome = $prevYearIncome * (1 + $COLA/100);
                    $prevYearIncome = $thisYearIncome;
                }

                // add income to income array
                $SSIncomes[] = $thisYearIncome;
            }

            return $SSIncomes;

        }   // end of function getSSIncomes


        // get retirement incomes
        // can only get parameters and dummy values.  Need data not calculated until in the retirementForecast blade.
        function  getRetParams($date) {

            // need 
            //  invWD
            //  RetDistribBegin
            //  WF-IRA-non-taxable-Roth
            //  WF-IRA-Taxable-Trad
            //  TIAA
            //  RetirementDisc
            //  (don't need WF-Inv-Bal - not retirement funds)
            //  LTCinWF#        (need to use 'like')
            //  LTCinWFdate#
            //  LTCinvGrowth
            //  GBLimoForExpenses
            //  GBMaxForExpenses
            //  
            // See 
            //  https://docs.google.com/spreadsheets/d/1R3-hUoFWOH0Uy_slsT8UsA8CUeDRr2heCF57Z44LCYo/edit?gid=0#gid=0
            //  ...for LTC details
            //  NOTES: 
            //      LTC is in Traditional IRA since LTC might be tax deductible
            //      ALL of Inherited IRA is considered earmarked for LTC

            // 'LTCinWF',       // use 'like', 'LTCinWF%' for LTCinWF and LTCinWFdate
            // 'LTCinWFdate',
            // 'Doctor20xx'     // use 'like', 'Doctor20%'
            $retirementParametersFields = [
                'invWD',
                'RetDistribBegin',
                'WF-IRA-non-taxable-Roth',
                'WF-IRA-Taxable-Trad',
                'TIAA',
                'RetirementDisc',
                'InvGrowth',
                'LTCinvGrowth',
                'HouseGrowth',
                'House',
                'GBLimoForExpenses',
                'GBMaxForExpenses',
                'SS-Med-WHs'
            ];

            // get the parameters from the retirementdata table
            $retirementParametersDB = DB::table('retirementdata')
                ->select('description', 'data', 'type')
                ->whereIn('type', ['inpt', 'assm', 'val'])
                ->whereNull('deleted_at')
                ->where(function($q) use ($retirementParametersFields) { 
                   $taxableRetIncomes[] = 0;
                $nonTaxableRetIncomes[] = 0; $q->whereIn('description', $retirementParametersFields)
                    ->orWhere('description', 'like', 'LTCinWF%')
                    ->orWhere('description', 'like', 'Doctor20%');
                }) 
                ->orderBy('description')
                ->get()->toArray();

            //  Reformat into associative array
            //  When more than one 'description', use the type 'inpt' value
            $retirementParameters = [];
            foreach($retirementParametersDB as $retDatum) {
                if(!isset($retirementParameters[$retDatum->description])) {
                    $retirementParameters[$retDatum->description] = $retDatum->data;
                } else {
                    if($retDatum->type == 'inpt') {
                        $retirementParameters[$retDatum->description] = $retDatum->data;
                    }
                }
            }

            error_log("new retirementParameters: ");
            foreach($retirementParameters as $idx=>$retDatum) {
                error_log($idx . ": " . json_encode($retDatum));
            }
                

            return $retirementParameters;

        }    // end function getRetParams
        
    
        function  getInvestmentGrowths($date) {
            // left off here
            $investmentGrowths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 11, 2, 3, 4, 5, 6, 7, 8, 9, 2, 2, 22, 23, 24, 25, 26, 27, 28, 29, 3, 4, 2, 3, 4, 5, 6, 7 ];
            return $investmentGrowths;

        }    // end function getInvestmentGrowths

        // months array
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get first of this month
        $date = date('Y-m-01'); // yyyy-mm-01 - first of current month
        $firstOfThisYear = substr($date, 0, 4) . '-01-01';
        $lastOfThisYear = substr($date, 0, 4) . '-12-31';
        $lastYear = date("Y") - 1;
        $firstOfLastYear = $lastYear . '-01-01';
        $lastOfLastYear = $lastYear . '-12-31';
        $twoDigitYear = substr($date, 2, 2);

        $thisMonthIdx = substr($date, 5, 2);
        $thisYear = substr($date, 0, 4);        

        // get beginning balances
        [$beginOfThisMonthSpendingBal, $invAcctsBal, $retTaxAcctsBal, $retNonTaxAcctsBal, $spendingAccts, $invAccts, $retTaxAccts, $retNonTaxAccts, $initLTCBal] = initialBalances($date, $twoDigitYear);
        // left off here -- fix this ... don't need placeholders anymore
        $spending = [$beginOfThisMonthSpendingBal, 29, 39, 49, 59, 69, 79, 89, 99, 199, 119, 129, 139, 149, 159, 169, 179, 189, 199, 299, 219, 229, 239, 249, 259, 269, 279, 289, 299, 399, 19, 29, 39, 49, 59, 69, 79, 89, 99, 199, 119, 129, 139, 149, 159, 169, 179, 189, 199, 299, 219, 229, 239, 249, 259, 269, 279, 289, 299, 399, 19, 29, 39, 49, 59, 69, 79, 89, 99, 199, 119, 129, 139, 149, 159, 169, 179, 189, 199, 299, 219, 229, 239, 249, 259, 269, 279, 289, 299, 399, 19, 29, 39, 49, 59, 69, 79, 89, 99, 199, 119 ];
        $investments = [$invAcctsBal, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ];
        $retirementTaxable = [$retTaxAcctsBal, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110 ];
        $retirementNonTaxable = [$retNonTaxAcctsBal, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111, 121, 131, 141, 151, 161, 171, 181, 191, 211, 211, 221, 231, 241, 251, 261, 271, 281, 291, 311, 11, 21, 31, 41, 51, 61, 71, 81, 91, 111, 111 ];
        $beginBalances = $this->addArraysByPosition($spending, $investments, $retirementTaxable, $retirementNonTaxable);

        // get rest of this year's expected income
        $remainingIncomeThisYear = thisYearIncome($date, $twoDigitYear);
        
        // get Town of Durham income estimates
        [$townOfDurhamIncomes, $raise, $COLA] = getTownOfDurhamIncomes($date);
        
        // get GB Lime income estimates
        $GBLimoIncomes = getGBLimoIncomes($date, $raise);
        
        // get rental income estimates
        $rentalIncomes = getRentalIncomes($date, $raise);
        
        // get NH Retirement income estimates
        $nhRetirementIncomes = getFixedIncomes($date, 'NHRetirement');
        
        // get Mike IBM income estimates
        $mikeIBMIncomes = getFixedIncomes($date, 'MTS-IBM-Retirement');
        
        // get Maura IBM income estimates
        // ... left off here ...
        $mauraIBMIncomes = getFixedIncomes($date, 'MauraIBM');
        
        // get Mike SS income estimates
        $mikeSSIncomes = getSSIncomes($date, $COLA, 'Mike');
        
        // get Maura SS income estimates
        $mauraSSIncomes = getSSIncomes($date, $COLA, 'Maura');
        
        // get retirement incomes per year estimates
        $retirementParameters = getRetParams($date);

        // get dummy expected investement growth per year
        $investmentGrowths = getInvestmentGrowths($date);
        
        // get dummy expected taxable ret income growth per year
        $taxableRetirementGrowths = getInvestmentGrowths($date);
        
        // get dummy expected nontaxable ret income growth per year
        $taxFreeRetirementGrowths = getInvestmentGrowths($date);

        // init taxableRetIncomes and nonTaxableRetIncomes for placeholders
        $taxableRetIncomes = [];
        $nonTaxableRetIncomes = [];
        // use this to make sure they have the right number of elements.
        $numberYearsForecast = 2062 - $thisYear + 1;
        for($idx = 0; $idx < $numberYearsForecast; $idx++) {
            $taxableRetIncomes[] = 0;
            $nonTaxableRetIncomes[] = 0;
        }

        $incomeValues = [
            // town of durham
            json_encode($townOfDurhamIncomes),
            // GB Limo
            json_encode($GBLimoIncomes),
            // Rental
            json_encode($rentalIncomes),
            // NH Retirement
            json_encode($nhRetirementIncomes),
            // Mike IBM
            json_encode($mikeIBMIncomes),
            // Mike SS
            json_encode($mikeSSIncomes),
            // Maura IBM
            json_encode($mauraIBMIncomes),
            // Maura SS
            json_encode($mauraSSIncomes),
            // Taxable retirement placeholders
            json_encode($taxableRetIncomes),
            // non-taxable retirement placeholders
            json_encode($nonTaxableRetIncomes),
            // Investment Growth
            json_encode($investmentGrowths),
            // taxable retirement growth
            json_encode($taxableRetirementGrowths),
            // non taxable retirement growth
            json_encode($taxFreeRetirementGrowths)
        ];

        // get expense categories
        $expenseCategoriesWithSummaryCats = DB::table('categories')
            ->distinct()
            ->select('name', 'summaryCategory')
            ->where('ie', 'E')
            ->get()->toArray();
        error_log("expenseCategoriesWithSummaryCats: ");
        foreach($expenseCategoriesWithSummaryCats as $sumcat) error_log(" - " . json_encode($sumcat));

        $sumCategoriesWithDetailCategories = [];
        foreach($expenseCategoriesWithSummaryCats as $expAndSumCat) {
            if(!isset($sumCategoriesWithDetailCategories[$expAndSumCat->summaryCategory])) {
                $sumCategoriesWithDetailCategories[$expAndSumCat->summaryCategory] = [];
            }
            $sumCategoriesWithDetailCategories[$expAndSumCat->summaryCategory][] = $expAndSumCat->name;
        }
        error_log("sumCategoriesWithDetailCategories:");
        foreach($sumCategoriesWithDetailCategories as $sum=>$det) {
            error_log(" - " . $sum . "; " . json_encode($det));
        }

        $expenseCategories = [];
        $expenseCategories = array_column($expenseCategoriesWithSummaryCats, 'name');
        error_log("expenseCategories: ");
        foreach($expenseCategories as $expcat) error_log(" - " . $expcat);
        
        // get expense sumcategories
        $expenseSummaryCategories = DB::table('categories')
            ->distinct()
            ->select('summaryCategory')
            ->where('ie', 'E')  // where income/expense is expense
            ->get()->toArray();
        $expenseSummaryCategories = array_column($expenseSummaryCategories, 'summaryCategory');
        error_log("expenseSummaryCategories: " . json_encode($expenseSummaryCategories));

        // get amt spent before current month (in this year) by category
        // error_log("type firstOfThisYear: " . gettype($firstOfThisYear) . ", " . $firstOfThisYear);
        // error_log("type lastOfThisYear: " . gettype($lastOfThisYear) . ", " . $lastOfThisYear);
        // error_log("type date: " . gettype($date) . ", " . $date);
        // error_log("type expenseCategories: " . gettype($expenseCategories));
        $monthsQueryArray = [];
        foreach($months as $monthIdx=>$month) {
            if($monthIdx+1 >= $thisMonthIdx) {
                $monthsQueryArray[] = $month;
            }
        }

        // Build aggregate expressions for each month column.
        $monthAggregates = collect($monthsQueryArray)
            ->map(function ($month) {
                return DB::raw("sum({$month}) as {$month}");
            })
            ->toArray();

        $expectedExpensesByCategory = DB::table('budget')
            ->where('year', $thisYear)
            ->select('category', 'inflationFactor', ...$monthAggregates)
            ->groupBy('category')
            ->get()->toArray();
        error_log("expectedExpensesByCategory:");
        foreach($expectedExpensesByCategory as $key=>$expExp) {
            error_log(" - " . $key . ": " . json_encode($expExp));
        }
        // get this year's budget left by category
        $expectedExpensesAfterTodayByCategory = [];
        $inflationFactors = [];
        $expectedExpensesAfterTodayTotal = 0;
        // error_log("total: " . $expectedExpensesAfterTodayTotal);
        foreach($expectedExpensesByCategory as $expensesRcd) {
            error_log("expensesRcd: " . json_encode($expensesRcd));
            $expectedExpensesAfterTodayByCategory[$expensesRcd->category] = 0;
            if($expensesRcd->inflationFactor != null) {
                $inflationFactors[$expensesRcd->category] = $expensesRcd->inflationFactor;
            }
            foreach($monthsQueryArray as $month) {
                $expectedExpensesAfterTodayByCategory[$expensesRcd->category] += $expensesRcd->{$month};
            }
        }

        // get default inflationFactor
        $defaultInflationFactor = DB::table('retirementdata')
            ->where('description', 'Inflation')
            ->whereNull('deleted_at')
            ->orderByDesc('type')       // so inpt - input (rather than assm - assumed) is first
            ->value('data');
        // error_log("defaultInflationFactor: " . $defaultInflationFactor);
        error_log("expectedExpensesAfterTodayByCategory: " . json_encode($expectedExpensesAfterTodayByCategory));
        error_log("inflationFactors: " . json_encode($inflationFactors));


        // init each summaryCategory total to 0
        $expectedExpensesAfterTodayBySUMMARYCategory = [];
        $actualExpensesAfterTodayBySUMMARYCategory = [];
        foreach($expenseSummaryCategories as $sumcategory) {
            $expectedExpensesAfterTodayBySUMMARYCategory[$sumcategory] = 0.00;
            $actualExpensesAfterTodayBySUMMARYCategory[$sumcategory] = 0.00;
            error_log("sumcategory: " . $sumcategory . ";  expectedExpensesAfterTodayBySUMMARYCategory[".$sumcategory."]: " . $expectedExpensesAfterTodayBySUMMARYCategory[$sumcategory]);
        }

        // sum subtotals for each sumcategory
        foreach($expectedExpensesAfterTodayByCategory as $idx=>$categoryRcd) {
            error_log("---- " . $idx . " ------");
            error_log($categoryRcd);
            $sumCat = collect($expenseCategoriesWithSummaryCats)
                // ->where('name', $categoryRcd->category)
                ->where('name', $idx)
                ->first();
            error_log("summary cat: " . json_encode($sumCat));
            if($sumCat != null) {
                error_log(" -- " . $sumCat->summaryCategory);
                $expectedExpensesAfterTodayBySUMMARYCategory[$sumCat->summaryCategory] += $categoryRcd;
                error_log("expectedExpensesAfterTodayBySUMMARYCategory[". $sumCat->summaryCategory . "]: " . $expectedExpensesAfterTodayBySUMMARYCategory[$sumCat->summaryCategory]);
                // $expectedExpensesAfterTodayTotal += $categoryRcd;
                // error_log("total: " . $expectedExpensesAfterTodayTotal);
            }
        }

        // for rest of year expenses, need max of budget or actuals.
        //      I have the budget.  Need to get actuals, sum by bigger category, and compare to budget.
        $restOfYearActualsDB = DB::table("transactions")
            ->select(
                'category',
                DB::raw('SUM(amount) AS amount')
            )
            ->whereNull('deleted_at')
            ->whereBetween('trans_date', [$date, $lastOfThisYear])
            ->where('account', 'not like', 'Maura%')
            ->where('account', 'not like', 'Mike%')
            ->whereIn('category', $expenseCategories)
            ->groupBy('category')
            ->get()
            ->toArray();

        error_log("============  restOfYearActualsDB ==========");
        foreach($restOfYearActualsDB as $rest) error_log(json_encode($rest));

        // FIX FORMAT to associative array where key is category -> amount
        $restOfYearActuals = [];
        foreach($restOfYearActualsDB as $restElmt) {
            $restOfYearActuals[$restElmt->category] = $restElmt->amount;
        }

        // if no spending for a category, set it to 0
        foreach($expenseCategories as $expenseCategory) {
            if(!isset($restOfYearActuals[$expenseCategory])) $restOfYearActuals[$expenseCategory] = 0.0;
        }

        error_log(" ---- restOfYearActuals  (no DB) --------- ");
        foreach($restOfYearActuals as $key=>$rest) error_log($key . ": " . $rest);

        // sum subtotals for each sumcategory for expenses this month and rest of year
        error_log(" --- ");
        error_log("restOfYear by summary cats:");
        foreach($restOfYearActuals as $idx=>$categoryRcd) {
            // error_log("---- " . $idx . " ------");
            // error_log($categoryRcd);
            $sumCat = collect($expenseCategoriesWithSummaryCats)
                ->where('name', $idx)
                ->first();
            // error_log("summary cat: " . json_encode($sumCat));
            if($sumCat != null) {
                // error_log(" -- " . $sumCat->summaryCategory);
                $actualExpensesAfterTodayBySUMMARYCategory[$sumCat->summaryCategory] += $categoryRcd;
            }
        }
        // error_log(" --------- actualExpensesAfterTodayBySUMMARYCategory --------");
        // foreach($actualExpensesAfterTodayBySUMMARYCategory as $key=>$actual) error_log($key . ": " . $actual);

        foreach($expectedExpensesAfterTodayBySUMMARYCategory as $key=>$expected) {
            // use "min" to get the bigger number since they are NEGATIVE.
            $expectedExpensesAfterTodayBySUMMARYCategory[$key] = min($expectedExpensesAfterTodayBySUMMARYCategory[$key], $actualExpensesAfterTodayBySUMMARYCategory[$key]);
            $expectedExpensesAfterTodayTotal += $expectedExpensesAfterTodayBySUMMARYCategory[$key];
        }

        // round to nearest dollar
        foreach($expectedExpensesAfterTodayBySUMMARYCategory as $sumcatRcdIdx=>$sumcatRcd) {
            $expectedExpensesAfterTodayBySUMMARYCategory[$sumcatRcdIdx] = round($sumcatRcd, 0);
        }
        $expectedExpensesAfterTodayTotal = round($expectedExpensesAfterTodayTotal, 0);
        
        // get expenses for the current year by category
        //      actuals for Jan - last month +
        //      expected expenses for rest of year
        $expectedExpensesForThisYearByCategory = [];
        // $actualExpensesYTMDB = DB::table('transactions')
        //     ->select('category', SUM('amount') as amount)
        //     ->where('trans_date', '>=', $firstOfThisYear)
        //     ->where('trans_date', '<', $date)
        //     ->get()->toArray();
        $actualExpensesYTM = DB::table('transactions')
            ->select('category', DB::raw('SUM(amount) as amount'))
            ->where('trans_date', '>=', $firstOfThisYear)
            ->where('trans_date', '<', $date)
            ->whereIn('category', $expenseCategories)
            ->groupBy('category')
            ->get()->toArray();
        error_log("actualExpensesYTM:" . json_encode($actualExpensesYTM));

        // get retirement income from last year (NOT SS, IBM, NH)
        $retirementAccts = ['WF-IRA', 'TIAA', 'DiscRet'];
        $lastYearRetirementIncome = DB::table('transactions')
            ->select('amount', 'toFrom', 'notes')
            ->whereBetween('trans_date', [$firstOfLastYear, $lastOfLastYear])  // last year
            ->where('category', 'IncomeRetirement')
            ->whereIn('toFrom', $retirementAccts)
            ->where('amount', '<', 0)                                           // only withdrawals from retirement accts
            ->whereNull('deleted_at')
            // ->where ('trans_date', '>', '2025-12-31')                           // nothing before 2026
            ->get()->toArray();
        // error_log("lastYearRetirementIncome:");
        // foreach($lastYearRetirementIncome as $retItm) {
        //     error_log(json_encode($retItm));
        // }           
            
            
        // keep track of categories so categories with no expenses yet can be appended
        $categoriesCalculated = [];
        foreach($actualExpensesYTM as $actual) {
            $category = $actual->category;
            $categoriesCalculated[] = $category;
            $expectedExpensesForThisYearByCategory[$category] =
                $actual->amount +
                ($expectedExpensesAfterTodayByCategory[$category] ?? 0);
        }
        // find categories missed and include those
        foreach($expenseCategories as $expenseCategory) {
            if(!in_array($expenseCategory, $categoriesCalculated)) {
                $expectedExpensesForThisYearByCategory[$expenseCategory] =
                    $expectedExpensesAfterTodayByCategory[$expenseCategory] ?? 0;
            }
        }

        // for MikeSpending & MauraSpending, expected expenses is based on budget, not history
        $MMSpending = DB::table('budget')
            ->select('category', 'total')
            ->where('year', $thisYear)
            ->whereIn('category', ['MikeSpending', 'MauraSpending'])
            ->whereNull('deleted_at')
            ->get()->toArray();

        foreach($MMSpending as $MorM) {
            $expectedExpensesForThisYearByCategory[$MorM->category] = $MorM->total;
        }
            
        error_log("expectedExpensesForThisYearByCategory:");
        foreach($expectedExpensesForThisYearByCategory as $cat=>$actual) {
            error_log(" - " . $cat . ": " . $actual);
        }
        error_log("-----");

        // get budgeted spending for the rest of the year, starting with all of this month
        // left off here...
        error_log("\n\n" . json_encode($expectedExpensesAfterTodayBySUMMARYCategory));
        error_log("keys: " . json_encode(array_keys($expectedExpensesAfterTodayBySUMMARYCategory)));

        error_log("incomeValues: " );
        foreach($incomeValues as $key=>$val) {
            error_log(" - " . $key . ": " . $val);
        }

        error_log("spending:");
        foreach($spending as $id=>$xxx) error_log($id . ": " . json_encode($xxx));

        return view('retirementForecast', compact('date', 'spending', 'investments', 'retirementTaxable', 'retirementNonTaxable', 'retirementParameters', 'beginBalances', 'incomeValues', 'expectedExpensesAfterTodayByCategory', 'expectedExpensesAfterTodayBySUMMARYCategory', 'expectedExpensesAfterTodayTotal', 'expenseCategoriesWithSummaryCats', 'sumCategoriesWithDetailCategories', 'expectedExpensesForThisYearByCategory', 'defaultInflationFactor', 'inflationFactors', 'spendingAccts', 'invAccts', 'retTaxAccts', 'retNonTaxAccts', 'initLTCBal', 'lastYearRetirementIncome'));
    }

}
