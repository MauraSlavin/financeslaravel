<h2>To start:</h2>
  <ul>
    <li><em>php artisan serve</em> (in VS Code)</li>
    <li>go to url: <em>localhost:8000</em> or <em>localhost:8000/accounts</em></li>
  </ul>

<h2>Old branch 2025-03-01-fix-splitting</h2>
<h2>Nothing being worked on - need new branch</h2>
    <ul>
      <li>
      </li>
    </ul>

<h2>BUGS</h2>
  <ul>
    <li>Error msg: The GET method is not supported for route transactions/insertTrans. Supported methods: POST.<br>
    GET /transactions/insertTrans</li>
  </ul>
    
<h2>To do:</h2>
    <ul>
      <li>fix saving new aliases</li>
      <li>**Manually** add some common aliases. (DiscCC, Checking, done; VISA partly done)</li>
      <li>button to go to Accounts from any page</li>
      <li>button to switch Mike/Maura on Spending page</li>
      <li>Button to add or delete notes</li>
      <li>Eliminate duplicate table (budget vs. newbudget)</li>
      <li>Update account totals at top on page for specific account when transaction added/deleted (Cleared balance; Register balance)
      <br>
      Maybe include unsaved changes as well as saved changes?  Unsaved in grey??</li>
      <li>budgetactuals page:<br>
      &nbsp&nbsp&nbspAbility to update budget for current year<br>
      &nbsp&nbsp&nbspClick on a Budget or Actual box to see the transactions included<br>
      &nbsp&nbsp&nbspDo I want to group these by category w/subtotals??</li>
      <li>No category or amtMike (for Maura's) / amtMaura (for Mike's) when adding transactions to Mike/Maura Spending accounts.</li>
      <li>adjust split_total when amount or total_key or total_amt changes.  Search: // handle splitTotal if amount is changed</li>
      <li>Set up column matches for all accounts (DiscCC and Checking done) - is this worth it?  Maybe Disc svgs?? I don't usually do a bulk upload for other accounts.</li>
      <li>Get saving aliases to work.  Include extraDefaults.</li>
      <li>Automate:<br>
      trips<br>
      </li>
      <li>Don't allow "Category" for spending accounts (Mike, MauraSCU, MauraDisc).  Remove from page for those accounts.</li>
      <li>In transactions.blade, a lot of the checking (see // transDate, // clearDate, etc) are similar.  Should they be combined into one reusable method?</li>
      <li>Different users so Mike can have copy of the application?</li>
      <li>Ability to change year in Spending view?</li>
      <li>Write transactions in Spending view to a file or Google sheet?</li>
    </ul>

<h2>Future Functionality:</h2>
  <ul>
    <li>Append Spending transactions to Google Sheets<br> -- https://www.phind.com/search?cache=f8twduhlg5g1fo4ca2bkrs7c</li>
    <li>Use tables (datatables?) that can sort & filter transactions</li>
    <li>Handle trips accounting<br>
    - automate each part of the cost calculations</li>
    <li>** Assets</li>
    <li> --- Include ability to set end date</li>
    <li>Loans?</li>
    <li>App for Marina's Miles??</li>
  </ul>

<h2>GIT notes:</h2>
  <ul>
    <li>git branch <branchname>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; // create a new branch</li>
    <li>git checkout <branchname>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  // work in new branch</li>
    <li>when ready to push changes...</li>
    <li>git add * &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; // includes all changes</li>
    <li>git commit -m <notes> &nbsp;&nbsp;&nbsp; // ready to push</li>
    <li>git push (again with -- suggested)</li>
    <li>log into github & create a merge request, and merge the code</li>
    <li>Back in VS Code...</li>
    <li>git pull (in branch - do I need this?)</li>
    <li>git checkout main</li>
    <li>git pull</li>
    <li>should now have all changes in the main branch, and ready to start again with a new branch</li>
  </ul>


<h2>uploadMatch Documentation</h2>
  <p>uploadMatch determines how each field in the transactions table gets filled from the csv download.</p>

  <h3>account_id</h3>
  <p>See account_id under "tofromaliases documentation" below.</p>

  <h3>csvField</h3>
  <p>The field in the downloaded csv field from the financial institution.</p>

  <h3>transField</h3>
  <p>The corresponding field in the local transactions table.</p>
  <p>NOTE: trans_date and clear_date look like "trans date" and "clear date" in the database.  The "_" is there, but not displaying in MySQL Workbench.</p>

  <h3>formulas</h3>
  <p>How the csv data needs to be manipulated to assign the transactions fields correctly.  This is what is allowed:</p>
  <ul>
    <li>+, -, *, /, numbers</li>
    <p>For example, "-.5*Amount" reverses the sign and multiplies the csvField ("Amount" in this case - see the uploadMatch table) by 0.5, and assigns the result to transField ("amtMike" or "amtMaura" in this case - see the uploadMatch table) in that transaction record.</p>
    <li>if x / then y / else z; in the form (x) ? (y) : z</li>
    <p>For example, "(Check Number) ? ('Ck #' . Check Number)  : '' " puts "Ck #123" in the method field if the Check Number is 123, and a null string if there is no check number</p>
    <li>concatenates fields using "+"</li>
    <p>For example: "Description + Memo" is used to combine these two fields in the csv file into the toFrom column of the transactions table for the Checking account. A space is inserted between them, and the resulting string is trimmed.  Only 2 fields can be concatenated.</p>
  </ul>

<h2>tofromaliases documentation</h2>

  <h3>account_id</h3>
  <p>account_ids are the ids of the accounts in the accounts table.  As of 1/8/25:</p>
  <ol>
    <li>Cash</li>
    <li>ChargePoint</li>
    <li>Checking</li>
    <li>DiscCC</li>
    <li>DiscRet</li>
    <li>DiscSavings</li>
    <li>ElectrifyAmerica</li>
    <li>* EJ</li>
    <li>Eversource</li>
    <li>FSA</li>
    <li>* House</li>
    <li>IrregBig (Disc)</li>
    <li>* JH</li>
    <li>LTC (Disc)</li>
    <li>MauraDisc</li>
    <li>MauraSCU</li>
    <li>Mike</li>
    <li>* Prudential</li>
    <li>* TIAA</li>
    <li>VISA</li>
    <li>* WF-Inv</li>
    <li>* WF-IRA</li>
  </ol>
  <p>* investment accounts (as opposed to transactional accounts).  A transactional account is something like a checking, savings, or cc; an investment account would be a CD or a retirement acct.

  <h3>origToFrom</h3>
  <p>...is the beginning of the verbiage in the downloaded csv file from the financial institution.  Only as much as needed for a match is in the table.</p>

  <h3>transToFrom (with IGNORE)</h3>
  <p>...is the desired toFrom verbiage in the local transactions table.</p>
  <p>If this is set to "IGNORE", the origToFrom is spliced from the string for the account indicated by the account_id.  This lets us ignore things like "External deposit" in the Checking csv download, and "sq*" in the Disc cc csv download to make matching that csv description easier to match to the local transactions toFrom field.</p>

  <h3>category</h3>
  <p>...is the default category for the toFrom text.</p>

  <h3>extraDefaults</h3>
  <p>...allow for default notes, tracking, and split categories.  A few different formats are allowed:</p>
  <ul>
    <li>"notes":"text" or "tracking":"text"</li>
      <p>Sets the notes or the tracking to the text given.  Can use both. For example: {"notes":"charging","tracking":"Bolt"}</p>
    <li>{"splits":["MauraSpending","MikeSpending","Kids"]}</li>
      <p>Creates duplicate transactions with different categories in addition to the default category.  The total_amt is divided equally among all the splits, and the total_key is the id of the original transaction. There's a limit of 10 transactions in the tofromaliases table (more can be created manually in the interface, if needed).</p>
    <li>{"splits":["Bolt"],"notes":"tolls","tracking":["CRZ","Bolt"]}</li>
      <p>Multiple default tracking (& notes, I think) can be given for each split.  In this case, two records are created: the original from the csv download, and a duplicate with a category of "Bolt".  The original transaction gets tracking "CRZ", and the duplicate gets tracking "Bolt".  Both get notes "tolls".  Each gets 1/2 the total amount.</p>
  </ul>

<h2>Variables Documentation - budgetactuals</h2>
  <h3>budgetData</h3>
  <p>Array where key is category (includes income and expense) with the monthly budget and yearly total budget for each. For example:<br>
  Bolt:&nbsp&nbsp&nbsp{<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"january":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"february":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"march":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"april":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"may":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"june":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"july":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"august":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"september":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"october":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"november":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"december":"-165.00",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp"total":"-1,980.00"<br>
  &nbsp&nbsp&nbsp}<br>


  <h3>actualIncomeData and actualExpenseData:</h3>
  <p>One element for each income or expense category, with the total amount for each month and a total actual income or expense for the year. For Example:<br>
  &nbsp&nbsp&nbspIncomeInterest:<br>
  &nbsp&nbsp&nbsp&nbsp{<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"january":"264.93",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"february":"263.90",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"march":"279.04",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"april":"268.24",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"may":"250.13",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"june":"237.77",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"july":"242.99",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"august":"320.59",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"september":"362.30",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"october":"261.22",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"november":"250.41",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"december":"260.81",<br>
  &nbsp&nbsp&nbsp&nbsp&nbsp"total":"3,262.33"<br>
  &nbsp&nbsp&nbsp&nbsp}</p>

  <h3>actualIncomeTotals, actualExpenseTotals, actualGrandTotals:</h3>
  <p>(incomeTotals, expenseTotals, grandTotals in actuals blade)</p>
  <p>Income, Expense or Grand (I + E) Total for each month, and for the year.  For example:<br>
  &nbsp&nbsp{<br>
  &nbsp&nbsp&nbsp"january":"23,604.49",<br>
  &nbsp&nbsp&nbsp"february":"10,898.23",<br>
  &nbsp&nbsp&nbsp"march":"12,006.59",<br>
  &nbsp&nbsp&nbsp"april":"11,499.51",<br>
  &nbsp&nbsp&nbsp"may":"9,326.36",<br>
  &nbsp&nbsp&nbsp"june":"9,173.47",<br>
  &nbsp&nbsp&nbsp"july":"10,659.22",<br>
  &nbsp&nbsp&nbsp"august":"17,809.69",<br>
  &nbsp&nbsp&nbsp"september":"11,008.01",<br>
  &nbsp&nbsp&nbsp"october":"11,625.71",<br>
  &nbsp&nbsp&nbsp"november":"12,020.77",<br>
  &nbsp&nbsp&nbsp"december":"16,192.86",<br>
  &nbsp&nbsp&nbsp"total":"155,824.91"<br>
  &nbsp&nbsp}

<h2>Database notes:</h2>
  <ul>
    <li>transactions: has each transaction</li>
    <li>bucketgoals:  goals for each bucket in Discover Savings account.</li>
    <li>budget: is still used in Workbench, I think.</li>
<br>
<p>New tables</p>
    <li><u>newbudget</u>:  has same info as budget, and is used in the application</li>
    <li><u>accounts</u>: accountName, description & lastStmtDate (null for end of month) for each defined account</li>
    <li><u>uploadmatch</u>: maps csv to transaction fields for each account</li>
    <li><u>tofromaliases</u>: convert what's in bank download to my verbiage for toFrom field</li>
    <br>
    <li>NOTE:  Delete budget table & rename newbudget to budget when done.</li>
  </ul>


<h2>routes: (am I missing some?)</h2>
  <ul>
    <li>Default Laravel welcome page
    <br>- <u>http://localhost:8000</u></li>
    <li>List of accounts and balances (including a line for all accounts at the bottom)
    <br>- <u>http://localhost:8000/accounts</u><br></li>
    <li>gets the last month of transactions for that account;  .../all gets last transactions for all accounts
    <br>- <u>http://localhost:8000/accounts/{accountName}</u><br></li>
    <li>gets transactions for that account for dates passed in;  .../all gets transactions for all accounts
    <br>- <u>http://localhost:8000/accounts/{accountName}/{beginDate}/{endDate}</u><br></li>
    <li>Upload transactions from public/uploadFiles/{accountName}.csv file for that account
    <br>- <u>http://localhost:8000/accounts/{accountName}/upload</u>
    <li>Delete a transaction by id
    <br>- <u>http://localhost:8000/transactions/delete/{id}</u>
    <li>Insert a record to tofromaliases
    <br>- <u>http://localhost:8000/transactions/insertAlias/{origToFrom}/{newValue}</u>
  </ul>

<h2>Error codes:</h2>
  <ul>
    <li>411 - No first header record in csv file being uploaded.</li>
    <li>412 - in http://localhost:8000/accounts/{accountName}/upload, account isn't a defined account.</li>
  </ul>

<h2>Cloning</h2>
  <ul>
    <li>git clone {copied url from github}</li>
    <li>composer install</li>
    <li>copy in .env</li>
    <li>php artisan key:generate</li>
  </ul>

  <p>Need to have installed:</p>
    <ul>
      <li>git</li>
      <li>composer</li>
      <li>artisan</li>
    </ul>


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
