<h2>To start:</h2>
  <ul><li>php artisan serve</li></ul>

<h2>Working on branch: 2025-01-05-default-fields-for-new-transaction</h2>
    <ul>
      <li>add records to toFromAliases for Checking (Disc sav?, other accounts?)
      <li>added records to toFromAliases - from DiscCC last 6 months (july - dec 24)</li>
    </ul>
    
<h2>To do:</h2>
    <ul>
      <li>** similar from when uploading when adding single transactions</li>
      <li>**Manually** add some common aliases. (DiscCC done)</li>
      <li>adjust split_total when amount or total_key or total_amt changes.  Search: // handle splitTotal if amount is changed</li>
      <li>Set up column matches for all accounts (DiscCC and Checking done) - is this worth it?  Maybe Disc svgs?? I don't usually do a bulk upload for other accounts.</li>
      <li>Get saving aliases to work.  Include extraDefaults.</li>
      <li>** Assets</li>
      <li> --- Include ability to set end date</li>
      <li>Add transactions by cloning a transaction line, instead of using the new form</li>
      <li>Don't allow "Category" for spending accounts (Mike, MauraSCU, MauraDisc).  Remove from page for those accounts.</li>
      <li>In transactions.blade, a lot of the checking (see // transDate, // clearDate, etc) are similar.  Should they be combined into one reusable method?</li>
    </ul>

<h2>Future Functionality:</h2>
  <ul>
    <li>** Handle Great Bay Limo</li>
    <li>** Page to update values of things like WF, JH, House, Prudential, etc. all on one page (can update values for each account separately, now)</li>
    <li>From CSV file, load data into database<br>
    - put matches between CSV & transactions table in a table to define the matches for each account (only done for DiscCC, and partially done for Checking - special processing done for checking csv file)</li>
    <li>Append Spending transactions to Google Sheets<br> -- https://www.phind.com/search?cache=f8twduhlg5g1fo4ca2bkrs7c</li>
    <li>Use tables (datatables?) that can sort & filter transactions</li>
    <li>Handle trips accounting<br>
    - automate each part of the cost calculations</li>
    <li>Spending?</li>
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

<h2>Database notes:</h2>
  <ul>
    <li>transactions: has each transaction</li>
    <li>bucketgoals:  goals for each bucket in Discover Savings account.</li>
    <li>budget: is still used in Workbench, I think.</li>
<br>
<p>New tables</p>
    <li><u>newBudget</u>:  has same info as budget, and is used in the application</li>
    <li><u>accounts</u>: accountName, description & lastStmtDate (null for end of month) for each defined account</li>
    <li><u>uploadmatch</u>: maps csv to transaction fields for each account</li>
    <li><u>tofromaliases</u>: convert what's in bank download to my verbiage for toFrom field</li>
    <br>
    <li>NOTE:  Delete budget table & rename newBudget to budget when done.</li>
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
    <li>Insert a record to toFromAliases
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
