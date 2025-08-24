<?php declare(strict_types = 1);

// odsl-/var/www/html/tests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/tests/UI/PostUITest.php' => 
    array (
      0 => 'df80b6e869261308c16e875f6fa41f2f9e25dfb6',
      1 => 
      array (
        0 => 'tests\\ui\\postuitest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\shoulddisplaypostlist',
        1 => 'tests\\ui\\shouldcreatenewpost',
        2 => 'tests\\ui\\shouldeditexistingpost',
        3 => 'tests\\ui\\shoulddeletepost',
        4 => 'tests\\ui\\shouldhandleresponsivelayout',
        5 => 'tests\\ui\\shouldsupportdarkmode',
        6 => 'tests\\ui\\login',
        7 => 'tests\\ui\\browseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/UITestCase.php' => 
    array (
      0 => '2573d08706569e0d14cdb68d1cf61e6e65839b3e',
      1 => 
      array (
        0 => 'tests\\ui\\uitestcase',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\setupbeforeclass',
        1 => 'tests\\ui\\teardownafterclass',
        2 => 'tests\\ui\\capturescreenshot',
        3 => 'tests\\ui\\assertelementvisible',
        4 => 'tests\\ui\\assertelementnotvisible',
        5 => 'tests\\ui\\asserttextpresent',
        6 => 'tests\\ui\\asserttextnotpresent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/CrossBrowserTest.php' => 
    array (
      0 => 'dd5650df293d0ad1b75f89e54ca3a16dcbda4379',
      1 => 
      array (
        0 => 'tests\\ui\\crossbrowsertest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\browseraction',
        1 => 'tests\\ui\\testbrowsercompatibility',
        2 => 'tests\\ui\\testbasicfunctionality',
        3 => 'tests\\ui\\testcssstyles',
        4 => 'tests\\ui\\testjavascriptfeatures',
        5 => 'tests\\ui\\testresponsivedesign',
        6 => 'tests\\ui\\performbrowseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/UI/UserExperienceTest.php' => 
    array (
      0 => 'dfd5a3dd7b2028edf311e251f5da36fa0e537a43',
      1 => 
      array (
        0 => 'tests\\ui\\userexperiencetest',
      ),
      2 => 
      array (
        0 => 'tests\\ui\\shouldmeetaccessibilitystandards',
        1 => 'tests\\ui\\shouldprovidegooduserinteraction',
        2 => 'tests\\ui\\shouldperformwell',
        3 => 'tests\\ui\\shouldhandleerrors',
        4 => 'tests\\ui\\testforminteraction',
        5 => 'tests\\ui\\testerrorhandling',
        6 => 'tests\\ui\\testloadingstates',
        7 => 'tests\\ui\\testtooltips',
        8 => 'tests\\ui\\testscrollperformance',
        9 => 'tests\\ui\\testdynamicloading',
        10 => 'tests\\ui\\testnetworkerrorhandling',
        11 => 'tests\\ui\\testformvalidationerrors',
        12 => 'tests\\ui\\testnotfoundpage',
        13 => 'tests\\ui\\browseraction',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Database/DatabaseConnectionTest.php' => 
    array (
      0 => '936ac7e58e06ad43e2c0f0b5757641b043ffe53c',
      1 => 
      array (
        0 => 'tests\\unit\\database\\databaseconnectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\database\\setup',
        1 => 'tests\\unit\\database\\createssingletonpdoinstance',
        2 => 'tests\\unit\\database\\executesquerysuccessfully',
        3 => 'tests\\unit\\database\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/PostRepositoryPerformanceTest.php' => 
    array (
      0 => '8fe0d71fe7919705262a755e4f052bb0fc2b504c',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\postrepositoryperformancetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testbulkinsertperformance',
        3 => 'tests\\unit\\repository\\testpaginationperformance',
        4 => 'tests\\unit\\repository\\testsearchperformance',
        5 => 'tests\\unit\\repository\\testmultipletagassignmentperformance',
        6 => 'tests\\unit\\repository\\testconcurrentviewsincrementperformance',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/PostRepositoryTest.php' => 
    array (
      0 => '7340c9e6fb22641f9be458e64732e04f2c31aa3c',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\postrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testcancreatepost',
        3 => 'tests\\unit\\repository\\testcanfindpostbyid',
        4 => 'tests\\unit\\repository\\testcanfindpostbyuuid',
        5 => 'tests\\unit\\repository\\testcanupdatepost',
        6 => 'tests\\unit\\repository\\testcandeletepost',
        7 => 'tests\\unit\\repository\\testcanpaginateposts',
        8 => 'tests\\unit\\repository\\testcangetpinnedposts',
        9 => 'tests\\unit\\repository\\testcansetpinnedstatus',
        10 => 'tests\\unit\\repository\\testcanincrementviews',
        11 => 'tests\\unit\\repository\\testshouldrollbackontagassignmenterror',
        12 => 'tests\\unit\\repository\\testshouldcommitontagassignmentsuccess',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/UserRepositoryTest.php' => 
    array (
      0 => '6b9047c7ec8328e3573dfa32cc72b7083076fbc3',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\userrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\setuptestdatabase',
        2 => 'tests\\unit\\repository\\createusersuccessfully',
        3 => 'tests\\unit\\repository\\updateusersuccessfully',
        4 => 'tests\\unit\\repository\\deleteusersuccessfully',
        5 => 'tests\\unit\\repository\\finduserbyuuid',
        6 => 'tests\\unit\\repository\\finduserbyusername',
        7 => 'tests\\unit\\repository\\finduserbyemail',
        8 => 'tests\\unit\\repository\\preventduplicateusername',
        9 => 'tests\\unit\\repository\\preventduplicateemail',
        10 => 'tests\\unit\\repository\\finduserbyid',
        11 => 'tests\\unit\\repository\\returnnullwhenusernotfound',
        12 => 'tests\\unit\\repository\\updatelastlogintime',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repository/IpRepositoryTest.php' => 
    array (
      0 => 'fd3dd236e9e82a53c31b7fa5d87a958b4013bda9',
      1 => 
      array (
        0 => 'tests\\unit\\repository\\iprepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repository\\setup',
        1 => 'tests\\unit\\repository\\createtesttables',
        2 => 'tests\\unit\\repository\\testcancreateiprule',
        3 => 'tests\\unit\\repository\\testcannotcreateinvalidipaddress',
        4 => 'tests\\unit\\repository\\testcancreatecidrrange',
        5 => 'tests\\unit\\repository\\testfindbyipaddress',
        6 => 'tests\\unit\\repository\\testgetbytype',
        7 => 'tests\\unit\\repository\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Cache/CacheManagerTest.php' => 
    array (
      0 => 'dabf36be367cb7238ed9b36cffb76031c93fed77',
      1 => 
      array (
        0 => 'tests\\unit\\cache\\cachemanagertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\cache\\setup',
        1 => 'tests\\unit\\cache\\testsetandget',
        2 => 'tests\\unit\\cache\\testgetwithdefault',
        3 => 'tests\\unit\\cache\\testhas',
        4 => 'tests\\unit\\cache\\testdelete',
        5 => 'tests\\unit\\cache\\testclear',
        6 => 'tests\\unit\\cache\\testremember',
        7 => 'tests\\unit\\cache\\testrememberforever',
        8 => 'tests\\unit\\cache\\testttlexpiration',
        9 => 'tests\\unit\\cache\\testmany',
        10 => 'tests\\unit\\cache\\testputmany',
        11 => 'tests\\unit\\cache\\testdeletepattern',
        12 => 'tests\\unit\\cache\\testincrement',
        13 => 'tests\\unit\\cache\\testdecrement',
        14 => 'tests\\unit\\cache\\testgetstats',
        15 => 'tests\\unit\\cache\\testcleanup',
        16 => 'tests\\unit\\cache\\testisvalidkey',
        17 => 'tests\\unit\\cache\\testcomplexdatatypes',
        18 => 'tests\\unit\\cache\\testdefaultttl',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Cache/CacheKeysTest.php' => 
    array (
      0 => 'd55788f3fece547132f6ac796f4eb14d64bbde4d',
      1 => 
      array (
        0 => 'tests\\unit\\cache\\cachekeystest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\cache\\testpostcachekey',
        1 => 'tests\\unit\\cache\\testpostbyuuidcachekey',
        2 => 'tests\\unit\\cache\\testpostlistcachekey',
        3 => 'tests\\unit\\cache\\testpinnedpostscachekey',
        4 => 'tests\\unit\\cache\\testpostsbycategorycachekey',
        5 => 'tests\\unit\\cache\\testuserpostscachekey',
        6 => 'tests\\unit\\cache\\testposttagscachekey',
        7 => 'tests\\unit\\cache\\testpostviewscachekey',
        8 => 'tests\\unit\\cache\\testusercachekey',
        9 => 'tests\\unit\\cache\\testuserbyemailcachekey',
        10 => 'tests\\unit\\cache\\testsystemconfigcachekey',
        11 => 'tests\\unit\\cache\\testtagpostscachekey',
        12 => 'tests\\unit\\cache\\testsearchresultscachekey',
        13 => 'tests\\unit\\cache\\testratelimitbyipcachekey',
        14 => 'tests\\unit\\cache\\testratelimitbyusercachekey',
        15 => 'tests\\unit\\cache\\testgetprefix',
        16 => 'tests\\unit\\cache\\testgetseparator',
        17 => 'tests\\unit\\cache\\testisvalidkeywithvalidkey',
        18 => 'tests\\unit\\cache\\testisvalidkeywithinvalidkey',
        19 => 'tests\\unit\\cache\\testparsevalidkey',
        20 => 'tests\\unit\\cache\\testparseinvalidkey',
        21 => 'tests\\unit\\cache\\testpatterngeneration',
        22 => 'tests\\unit\\cache\\testuserpattern',
        23 => 'tests\\unit\\cache\\testpostpattern',
        24 => 'tests\\unit\\cache\\testpostslistpattern',
        25 => 'tests\\unit\\cache\\teststatspattern',
        26 => 'tests\\unit\\cache\\testdailystatscachekey',
        27 => 'tests\\unit\\cache\\testmonthlystatscachekey',
        28 => 'tests\\unit\\cache\\testcachekeyconsistency',
        29 => 'tests\\unit\\cache\\testcachekeyuniqueness',
        30 => 'tests\\unit\\cache\\testemptyparameterhandling',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/ExampleTest.php' => 
    array (
      0 => '70bcefac6518229d96177306687e327f43d3d3f3',
      1 => 
      array (
        0 => 'tests\\unit\\exampletest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\testbasictest',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Repositories/AttachmentRepositoryTest.php' => 
    array (
      0 => '4e307c5c29772ac2a26021f10eaadfeb439addb8',
      1 => 
      array (
        0 => 'tests\\unit\\repositories\\attachmentrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\repositories\\setup',
        1 => 'tests\\unit\\repositories\\createtesttables',
        2 => 'tests\\unit\\repositories\\shouldcreateattachmentsuccessfully',
        3 => 'tests\\unit\\repositories\\shouldfindattachmentbyid',
        4 => 'tests\\unit\\repositories\\shouldfindattachmentbyuuid',
        5 => 'tests\\unit\\repositories\\shouldreturnnullfornonexistentid',
        6 => 'tests\\unit\\repositories\\shouldreturnnullfornonexistentuuid',
        7 => 'tests\\unit\\repositories\\shouldgetattachmentsbypostid',
        8 => 'tests\\unit\\repositories\\shouldsoftdeleteattachment',
        9 => 'tests\\unit\\repositories\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Models/PostTest.php' => 
    array (
      0 => 'de13afbcf0be19af4cf14ca3599f2e4d4f178eed',
      1 => 
      array (
        0 => 'tests\\unit\\models\\posttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\correctlyinitializeswithvaliddata',
        1 => 'tests\\unit\\models\\handlesnullablefieldscorrectly',
        2 => 'tests\\unit\\models\\setsdefaultvaluescorrectly',
        3 => 'tests\\unit\\models\\properlyescapeshtmlintitleandcontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Exceptions/PostNotFoundExceptionTest.php' => 
    array (
      0 => 'dbe5bf4875dcd273f2305c2419f2c52875224583',
      1 => 
      array (
        0 => 'tests\\unit\\exceptions\\postnotfoundexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\exceptions\\testconstructorwithpostid',
        1 => 'tests\\unit\\exceptions\\testconstructorwithcustommessage',
        2 => 'tests\\unit\\exceptions\\testbyidstaticmethod',
        3 => 'tests\\unit\\exceptions\\testbyuuidstaticmethod',
        4 => 'tests\\unit\\exceptions\\testinheritsfromnotfoundexception',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/Post/CreatePostDTOTest.php' => 
    array (
      0 => 'b99fb8f3447153781cedcde2f3b4cc4328f82cc6',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\createpostdtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\testcancreatedtowithvaliddata',
        1 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingrequiredfield',
        2 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidip',
        3 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionfortitletoolong',
        4 => 'tests\\unit\\dtos\\post\\testtoarrayreturnscorrectformat',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Http/ApiResponseTest.php' => 
    array (
      0 => '84720ddca590cce10fe1b9b8ed9c9819ce250d70',
      1 => 
      array (
        0 => 'tests\\unit\\http\\apiresponsetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\http\\testsuccessresponse',
        1 => 'tests\\unit\\http\\testsuccessresponsewithdefaults',
        2 => 'tests\\unit\\http\\testerrorresponse',
        3 => 'tests\\unit\\http\\testerrorresponsewithdefaults',
        4 => 'tests\\unit\\http\\testpaginatedresponse',
        5 => 'tests\\unit\\http\\testtimestampformat',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Controllers/IpControllerTest.php' => 
    array (
      0 => '98cb658ba06251a25838e8674642b0ac8236171c',
      1 => 
      array (
        0 => 'tests\\unit\\controllers\\ipcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\controllers\\setup',
        1 => 'tests\\unit\\controllers\\testcancreateiprule',
        2 => 'tests\\unit\\controllers\\testcannotcreatewithinvaliddata',
        3 => 'tests\\unit\\controllers\\testcanlistrulesbytype',
        4 => 'tests\\unit\\controllers\\testcancheckipaccess',
        5 => 'tests\\unit\\controllers\\testcannotcheckinvalidip',
        6 => 'tests\\unit\\controllers\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Factory/PostFactoryTest.php' => 
    array (
      0 => 'db741274ab66072c13df8a0812e175d47684f5cc',
      1 => 
      array (
        0 => 'tests\\unit\\factory\\postfactorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\factory\\testitcanmakepostdata',
        1 => 'tests\\unit\\factory\\testitcancreatepostindatabase',
        2 => 'tests\\unit\\factory\\testitcanoverridedefaultattributes',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/XssProtectionServiceTest.php' => 
    array (
      0 => '9c2fbc437dd89694abe33877261ab2f1ac3546f4',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\xssprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\escapesbasichtml',
        2 => 'tests\\unit\\services\\security\\escapeshtmlattributes',
        3 => 'tests\\unit\\services\\security\\handlesnullinput',
        4 => 'tests\\unit\\services\\security\\cleansarrayofstrings',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/SessionSecurityServiceTest.php' => 
    array (
      0 => 'eea9e5fbbfed803205c5da8e6499ec531085be1f',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\sessionsecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\initializessecuresessioninproduction',
        3 => 'tests\\unit\\services\\security\\initializessecuresessionindevelopment',
        4 => 'tests\\unit\\services\\security\\setsusersessionwithuseragentbinding',
        5 => 'tests\\unit\\services\\security\\validatesuseragentcorrectly',
        6 => 'tests\\unit\\services\\security\\validatessessionipcorrectly',
        7 => 'tests\\unit\\services\\security\\performscomprehensivesecuritycheck',
        8 => 'tests\\unit\\services\\security\\detectsuseragentchange',
        9 => 'tests\\unit\\services\\security\\detectsipchange',
        10 => 'tests\\unit\\services\\security\\handlesipverificationflow',
        11 => 'tests\\unit\\services\\security\\detectsexpiredsession',
        12 => 'tests\\unit\\services\\security\\updatesactivitytime',
        13 => 'tests\\unit\\services\\security\\destroyssessionsecurely',
        14 => 'tests\\unit\\services\\security\\regeneratessessionid',
        15 => 'tests\\unit\\services\\security\\handlesmissingsessiondata',
        16 => 'tests\\unit\\services\\security\\handlesipverificationtimeout',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/LoggingSecurityServiceTest.php' => 
    array (
      0 => 'c048082d1e338695157f4c636c743d8ac509e12e',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\loggingsecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\storage_path',
        2 => 'tests\\unit\\services\\security\\teardown',
        3 => 'tests\\unit\\services\\security\\recursivedelete',
        4 => 'tests\\unit\\services\\security\\sanitizescontextdatacorrectly',
        5 => 'tests\\unit\\services\\security\\appliesrequestwhitelistcorrectly',
        6 => 'tests\\unit\\services\\security\\truncateslongstrings',
        7 => 'tests\\unit\\services\\security\\enrichessecuritycontext',
        8 => 'tests\\unit\\services\\security\\enrichesrequestcontext',
        9 => 'tests\\unit\\services\\security\\logssecurityeventscorrectly',
        10 => 'tests\\unit\\services\\security\\logsrequestswithwhitelist',
        11 => 'tests\\unit\\services\\security\\handlessensitivefieldvariations',
        12 => 'tests\\unit\\services\\security\\handlesemptyandnullvalues',
        13 => 'tests\\unit\\services\\security\\returnslogstatistics',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/PwnedPasswordServiceTest.php' => 
    array (
      0 => 'b302b9af85b8b13a49505812d4dea806aff65e20',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\pwnedpasswordservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\testshoulddetectcommonpwnedpassword',
        2 => 'tests\\unit\\services\\security\\testshouldnotdetectsecurepassword',
        3 => 'tests\\unit\\services\\security\\testshouldhandleapifailuregracefully',
        4 => 'tests\\unit\\services\\security\\testshouldvalidateapistatus',
        5 => 'tests\\unit\\services\\security\\testshouldhandlemultiplepasswords',
        6 => 'tests\\unit\\services\\security\\testshouldcacheresults',
        7 => 'tests\\unit\\services\\security\\testshouldclearcache',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/CsrfProtectionServiceTest.php' => 
    array (
      0 => '28035ed4407d303591bebebb880ef8c1334f39cf',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\csrfprotectionservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\teardown',
        2 => 'tests\\unit\\services\\security\\generatesvalidtoken',
        3 => 'tests\\unit\\services\\security\\validatescorrecttoken',
        4 => 'tests\\unit\\services\\security\\throwsexceptionforemptytoken',
        5 => 'tests\\unit\\services\\security\\throwsexceptionforinvalidtoken',
        6 => 'tests\\unit\\services\\security\\throwsexceptionforexpiredtoken',
        7 => 'tests\\unit\\services\\security\\updatestokenaftersuccessfulvalidation',
        8 => 'tests\\unit\\services\\security\\initializestokenpool',
        9 => 'tests\\unit\\services\\security\\supportsmultiplevalidtokensinpool',
        10 => 'tests\\unit\\services\\security\\validatestokenfrompoolwithconstanttimecomparison',
        11 => 'tests\\unit\\services\\security\\removestokenfrompoolafteruse',
        12 => 'tests\\unit\\services\\security\\cleansexpiredtokensfrompool',
        13 => 'tests\\unit\\services\\security\\limitstokenpoolsize',
        14 => 'tests\\unit\\services\\security\\gettokenpoolstatusreturnscorrectinfo',
        15 => 'tests\\unit\\services\\security\\fallsbacktosingletokenmodewhenpoolnotinitialized',
        16 => 'tests\\unit\\services\\security\\istokenvalidreturnsfalseforinvalidtoken',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/PostServiceTest.php' => 
    array (
      0 => '2e3b4828f3aca53e806dc3c02e43a15ca7246dc5',
      1 => 
      array (
        0 => 'tests\\unit\\services\\postservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testcreatepostwithvaliddto',
        2 => 'tests\\unit\\services\\testupdatepostwithvaliddto',
        3 => 'tests\\unit\\services\\testupdatepostwithinvalidstatustransition',
        4 => 'tests\\unit\\services\\testupdatenonexistentpost',
        5 => 'tests\\unit\\services\\testupdatepostwithnochanges',
        6 => 'tests\\unit\\services\\testdeletepost',
        7 => 'tests\\unit\\services\\testdeletepostwithrepositoryexception',
        8 => 'tests\\unit\\services\\testdeletepostwithgeneralexception',
        9 => 'tests\\unit\\services\\testfindbyid',
        10 => 'tests\\unit\\services\\testfindbyidnotfound',
        11 => 'tests\\unit\\services\\testlistposts',
        12 => 'tests\\unit\\services\\testgetpinnedposts',
        13 => 'tests\\unit\\services\\testsetpinned',
        14 => 'tests\\unit\\services\\testsetpinnedwithrepositoryexception',
        15 => 'tests\\unit\\services\\testsetpinnedwithgeneralexception',
        16 => 'tests\\unit\\services\\testsettags',
        17 => 'tests\\unit\\services\\testsettagswithnonexistentpost',
        18 => 'tests\\unit\\services\\testrecordview',
        19 => 'tests\\unit\\services\\testrecordviewwithinvalidip',
        20 => 'tests\\unit\\services\\testrecordviewfornonpublishedpost',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Enums/PostStatusTest.php' => 
    array (
      0 => '3e5cad6b4803b21bb64ad6f5953b01ce83b22864',
      1 => 
      array (
        0 => 'tests\\unit\\services\\enums\\poststatustest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\enums\\testgetlabel',
        1 => 'tests\\unit\\services\\enums\\testcantransitionto',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/CacheServiceTest.php' => 
    array (
      0 => '078a0e9c58a89137d45c690d3448e72e9eccc116',
      1 => 
      array (
        0 => 'tests\\unit\\services\\cacheservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\storeandretrievedata',
        2 => 'tests\\unit\\services\\handleconnectionfailure',
        3 => 'tests\\unit\\services\\handleconcurrentrequests',
        4 => 'tests\\unit\\services\\clearcache',
        5 => 'tests\\unit\\services\\deletespecifickey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AuthServiceTest.php' => 
    array (
      0 => '328ccbb4f493073d3f4ed0da129ab3be3ffc6917',
      1 => 
      array (
        0 => 'tests\\unit\\services\\authservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\teardown',
        2 => 'tests\\unit\\services\\it_should_register_new_user_successfully',
        3 => 'tests\\unit\\services\\it_should_validate_registration_data',
        4 => 'tests\\unit\\services\\it_should_login_user_successfully',
        5 => 'tests\\unit\\services\\it_should_fail_login_with_invalid_credentials',
        6 => 'tests\\unit\\services\\it_should_not_login_inactive_user',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/AttachmentServiceTest.php' => 
    array (
      0 => 'e9e8ec1fce544331b6e703d2c485b0b827332b0b',
      1 => 
      array (
        0 => 'tests\\unit\\services\\attachmentservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\shoulduploadfilesuccessfully',
        2 => 'tests\\unit\\services\\shouldrejectinvalidfiletype',
        3 => 'tests\\unit\\services\\shouldrejectoversizedfile',
        4 => 'tests\\unit\\services\\shouldrejectuploadtononexistentpost',
        5 => 'tests\\unit\\services\\createuploadedfilemock',
        6 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/IpServiceTest.php' => 
    array (
      0 => '676ff4e7a01b8a19a4318dba4ba4e3ac4cfbc737',
      1 => 
      array (
        0 => 'tests\\unit\\services\\ipservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\testcancreateiprule',
        2 => 'tests\\unit\\services\\testcannotcreateinvalidiprule',
        3 => 'tests\\unit\\services\\testcannotcreatewithinvalidtype',
        4 => 'tests\\unit\\services\\testcancheckipaccess',
        5 => 'tests\\unit\\services\\testcanvalidatecidrrange',
        6 => 'tests\\unit\\services\\testcangetrulesbytype',
        7 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/RateLimitServiceTest.php' => 
    array (
      0 => '3eede69db6bf387cdeb332f953dd5869bb137fbc',
      1 => 
      array (
        0 => 'tests\\unit\\services\\ratelimitservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\setup',
        1 => 'tests\\unit\\services\\shouldallowfirstrequest',
        2 => 'tests\\unit\\services\\shouldrejectwhenlimitexceeded',
        3 => 'tests\\unit\\services\\shouldhandlecachefailuregracefully',
        4 => 'tests\\unit\\services\\shouldincrementrequestcount',
        5 => 'tests\\unit\\services\\shouldhandlesetfailure',
        6 => 'tests\\unit\\services\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/CsrfProtectionTest.php' => 
    array (
      0 => '192f419c050532078889c83e838ab91cec58750d',
      1 => 
      array (
        0 => 'tests\\security\\csrfprotectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldrejectrequestwithoutcsrftoken',
        2 => 'tests\\security\\shouldrejectrequestwithinvalidcsrftoken',
        3 => 'tests\\security\\shouldacceptrequestwithvalidcsrftoken',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/SqlInjectionTest.php' => 
    array (
      0 => 'e5bf03931b5e358a034cd8d2d1450ec7dbea8757',
      1 => 
      array (
        0 => 'tests\\security\\sqlinjectiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\shouldpreventsqlinjectionintitlesearch',
        3 => 'tests\\security\\shouldhandlespecialcharactersincontent',
        4 => 'tests\\security\\shouldpreventsqlinjectioninuseridfilter',
        5 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/XssPreventionTest.php' => 
    array (
      0 => '7016e7a6e12c5f598527dc97ad83313898930147',
      1 => 
      array (
        0 => 'tests\\security\\xsspreventiontest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldescapehtmlinposttitle',
        2 => 'tests\\security\\shouldescapehtmlinpostcontent',
        3 => 'tests\\security\\shouldhandleencodedxssattempts',
        4 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/FileUploadSecurityTest.php' => 
    array (
      0 => '8492c36d1204b2c852b1f6016345d084b1807c13',
      1 => 
      array (
        0 => 'tests\\security\\fileuploadsecuritytest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\shouldrejectexecutablefiles',
        2 => 'tests\\security\\shouldrejectdoubleextensionfiles',
        3 => 'tests\\security\\shouldrejectoversizedfiles',
        4 => 'tests\\security\\shouldrejectmaliciousmimetypes',
        5 => 'tests\\security\\shouldpreventpathtraversal',
        6 => 'tests\\security\\createuploadedfilemock',
        7 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/PasswordHashingTest.php' => 
    array (
      0 => '6481beb847957b668d5392858ce6bff9e9441b8e',
      1 => 
      array (
        0 => 'tests\\security\\passwordhashingtest',
      ),
      2 => 
      array (
        0 => 'tests\\security\\setup',
        1 => 'tests\\security\\createtesttables',
        2 => 'tests\\security\\shouldhashpasswordusingargon2id',
        3 => 'tests\\security\\shoulduseappropriatehashingoptions',
        4 => 'tests\\security\\shouldrejectweakpasswords',
        5 => 'tests\\security\\shouldpreventpasswordreuse',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/security_test_runner.php' => 
    array (
      0 => '0b4e5b375d3b87f5396cca84eb6cecc0986f1a6f',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'showhelp',
        1 => 'runcategorytest',
        2 => 'outputtext',
        3 => 'outputjson',
        4 => 'outputxml',
        5 => 'arraytoxml',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/RateLimitTest.php' => 
    array (
      0 => 'd6be4f9822ca03c4e550a057b1eb3ceea97bc5dc',
      1 => 
      array (
        0 => 'tests\\integration\\ratelimittest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\shouldlimitratesuccessfully',
        2 => 'tests\\integration\\shouldresetlimitaftertimewindow',
        3 => 'tests\\integration\\shouldhandledifferentipsindependently',
        4 => 'tests\\integration\\shouldhandleserviceunavailability',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AuthControllerTest.php' => 
    array (
      0 => '005a86b4689965ec2b647a86d0be32b1352a9bed',
      1 => 
      array (
        0 => 'tests\\integration\\authcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\getparsedbody',
        2 => 'tests\\integration\\withparsedbody',
        3 => 'tests\\integration\\getheader',
        4 => 'tests\\integration\\withheader',
        5 => 'tests\\integration\\withstatus',
        6 => 'tests\\integration\\getstatuscode',
        7 => 'tests\\integration\\withjson',
        8 => 'tests\\integration\\getbody',
        9 => 'tests\\integration\\teardown',
        10 => 'tests\\integration\\registerusersuccessfully',
        11 => 'tests\\integration\\returnvalidationerrorsforinvalidregistrationdata',
        12 => 'tests\\integration\\loginusersuccessfully',
        13 => 'tests\\integration\\returnerrorforinvalidlogin',
        14 => 'tests\\integration\\logoutusersuccessfully',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Repositories/PostRepositoryTest.php' => 
    array (
      0 => '5064adbdc8c24afa01b8bfa8e32babba5350ff12',
      1 => 
      array (
        0 => 'tests\\integration\\repositories\\postrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\repositories\\setup',
        1 => 'tests\\integration\\repositories\\teardown',
        2 => 'tests\\integration\\repositories\\createtesttables',
        3 => 'tests\\integration\\repositories\\createtestpost',
        4 => 'tests\\integration\\repositories\\testcreatepost',
        5 => 'tests\\integration\\repositories\\testfindpostbyid',
        6 => 'tests\\integration\\repositories\\testfindnonexistentpost',
        7 => 'tests\\integration\\repositories\\testfindpostbyuuid',
        8 => 'tests\\integration\\repositories\\testfindpostbyseqnumber',
        9 => 'tests\\integration\\repositories\\testupdatepost',
        10 => 'tests\\integration\\repositories\\testsoftdeletepost',
        11 => 'tests\\integration\\repositories\\testpaginateposts',
        12 => 'tests\\integration\\repositories\\testgetpinnedposts',
        13 => 'tests\\integration\\repositories\\testsetpinned',
        14 => 'tests\\integration\\repositories\\testincrementviews',
        15 => 'tests\\integration\\repositories\\testincrementviewswithinvalidip',
        16 => 'tests\\integration\\repositories\\testsettags',
        17 => 'tests\\integration\\repositories\\testgetpostsbytag',
        18 => 'tests\\integration\\repositories\\testsecurityvalidationfordisallowedfields',
        19 => 'tests\\integration\\repositories\\testdeletedpostsarenotreturned',
        20 => 'tests\\integration\\repositories\\testtransactionrollbackonerror',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AttachmentControllerTest.php' => 
    array (
      0 => '13f63b8f6a0e24826a367007411abf9176f7bf37',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\uploadshouldstorefilesuccessfully',
        2 => 'tests\\integration\\uploadshouldreturn400forinvalidfile',
        3 => 'tests\\integration\\listshouldreturnattachments',
        4 => 'tests\\integration\\deleteshouldremoveattachment',
        5 => 'tests\\integration\\deleteshouldreturn404fornonexistentattachment',
        6 => 'tests\\integration\\deleteshouldreturn400forinvaliduuid',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/PostControllerTest.php' => 
    array (
      0 => '7562aecefffc2714c3d04e6ddb9df7b0bd943f14',
      1 => 
      array (
        0 => 'tests\\integration\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\indexshouldreturnpaginatedposts',
        2 => 'tests\\integration\\showshouldreturnpostdetails',
        3 => 'tests\\integration\\storeshouldcreatenewpost',
        4 => 'tests\\integration\\storeshouldreturn400whenvalidationfails',
        5 => 'tests\\integration\\updateshouldmodifyexistingpost',
        6 => 'tests\\integration\\updateshouldreturn404whenpostnotfound',
        7 => 'tests\\integration\\destroyshoulddeletepost',
        8 => 'tests\\integration\\updatepinstatusshouldupdatepinstatus',
        9 => 'tests\\integration\\updatepinstatusshouldreturn422wheninvalidstatetransition',
        10 => 'tests\\integration\\teardown',
        11 => 'tests\\integration\\createrequestmock',
        12 => 'tests\\integration\\createstreammock',
        13 => 'tests\\integration\\createresponsemock',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Http/PostControllerTest.php' => 
    array (
      0 => '4c40b89c0ae58f2d6fb1c80a8a5973728e2de842',
      1 => 
      array (
        0 => 'tests\\integration\\http\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\http\\setup',
        1 => 'tests\\integration\\http\\testgetpostsreturnssuccessresponse',
        2 => 'tests\\integration\\http\\testgetpostswithpaginationparameters',
        3 => 'tests\\integration\\http\\testgetpostswithsearchfilter',
        4 => 'tests\\integration\\http\\testgetpostswithstatusfilter',
        5 => 'tests\\integration\\http\\testgetpostswithinvalidlimitreturnsvalidationerror',
        6 => 'tests\\integration\\http\\testcreatepostwithvaliddata',
        7 => 'tests\\integration\\http\\testcreatepostwithinvalidjsonreturnserror',
        8 => 'tests\\integration\\http\\testcreatepostwithmissingrequiredfields',
        9 => 'tests\\integration\\http\\testgetpostbyidreturnssuccess',
        10 => 'tests\\integration\\http\\testgetnonexistentpostreturnsnotfound',
        11 => 'tests\\integration\\http\\testgetpostwithinvalididreturnserror',
        12 => 'tests\\integration\\http\\testupdatepostwithvaliddata',
        13 => 'tests\\integration\\http\\testupdatenonexistentpostreturnsnotfound',
        14 => 'tests\\integration\\http\\testdeletepost',
        15 => 'tests\\integration\\http\\testdeletenonexistentpostreturnsnotfound',
        16 => 'tests\\integration\\http\\testtogglepostpin',
        17 => 'tests\\integration\\http\\testtogglepostpinwithinvaliddata',
        18 => 'tests\\integration\\http\\testapiresponsestructureconsistency',
        19 => 'tests\\integration\\http\\testhealthendpoint',
        20 => 'tests\\integration\\http\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/IpManagementTest.php' => 
    array (
      0 => '6036ce9cebe98ed4743f3b60ffb641beb13e5ce5',
      1 => 
      array (
        0 => 'tests\\integration\\ipmanagementtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\seedtestdata',
        2 => 'tests\\integration\\createtesttables',
        3 => 'tests\\integration\\testcompleteipmanagementflow',
        4 => 'tests\\integration\\testerrorhandling',
        5 => 'tests\\integration\\testcacheintegration',
        6 => 'tests\\integration\\testconcurrentoperations',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/FileSystemBackupTest.php' => 
    array (
      0 => '68c52d5e77e6fb17df69ec6e5d7951c9b95c0ba9',
      1 => 
      array (
        0 => 'tests\\integration\\filesystembackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtestfiles',
        2 => 'tests\\integration\\backupfilessuccessfully',
        3 => 'tests\\integration\\restorefilessuccessfully',
        4 => 'tests\\integration\\handlebackuperrorsgracefully',
        5 => 'tests\\integration\\handlerestoreerrorsgracefully',
        6 => 'tests\\integration\\handlepermissionerrors',
        7 => 'tests\\integration\\maintainfilemetadataduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DatabaseBackupTest.php' => 
    array (
      0 => 'bbd96b89e6c0df5ddb08369ea2dfe9c3b593b606',
      1 => 
      array (
        0 => 'tests\\integration\\databasebackuptest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createtesttables',
        2 => 'tests\\integration\\inserttestdata',
        3 => 'tests\\integration\\backupdatabasesuccessfully',
        4 => 'tests\\integration\\restoredatabasesuccessfully',
        5 => 'tests\\integration\\handlebackuperrorsgracefully',
        6 => 'tests\\integration\\handlerestoreerrorsgracefully',
        7 => 'tests\\integration\\maintaindataintegrityduringbackuprestore',
        8 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AttachmentUploadTest.php' => 
    array (
      0 => '29fac80e3ed438db41fb53a0a91062204f9dea20',
      1 => 
      array (
        0 => 'tests\\integration\\attachmentuploadtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\createuploadedfilemock',
        2 => 'tests\\integration\\should_handle_concurrent_uploads',
        3 => 'tests\\integration\\should_handle_large_file_upload',
        4 => 'tests\\integration\\should_validate_file_types',
        5 => 'tests\\integration\\should_handle_disk_full_error',
        6 => 'tests\\integration\\should_handle_permission_error',
        7 => 'tests\\integration\\teardown',
        8 => 'tests\\integration\\createtesttables',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/TestCase.php' => 
    array (
      0 => '46b32cb453138ca5175f2d8983f37ceac575e1d7',
      1 => 
      array (
        0 => 'tests\\testcase',
      ),
      2 => 
      array (
        0 => 'tests\\setup',
        1 => 'tests\\createtesttables',
        2 => 'tests\\createindices',
        3 => 'tests\\createresponsemock',
        4 => 'tests\\teardown',
      ),
      3 => 
      array (
      ),
    ),
  ),
));