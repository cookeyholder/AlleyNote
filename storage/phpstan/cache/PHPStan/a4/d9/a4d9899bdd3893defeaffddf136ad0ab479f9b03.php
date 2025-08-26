<?php declare(strict_types = 1);

// odsl-/var/www/html/tests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/tests/UI/PostUITest.php' => 
    array (
      0 => '80b69870cbe2932d3a63ce4eca8504065f852ad7',
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
      0 => '4dbd983bf464b60bbb8db282922a3ce4ec5fb150',
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
      0 => '087aacaa63829b5e75103f9da0056473dd3f34b6',
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
      0 => '7b2fd5d69ede6230d90779e8c8e5946971b8131e',
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
      0 => '4c8412dbb95ae9139efae3b0131fe738393eeae8',
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
      0 => '7aa3e4a7458ef45c29f055dc12f54fe913f728c5',
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
      0 => '5fa833e1686278a987caad522268c1bc5893d372',
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
      0 => '3cc42f33899d5c939affdebc17468adb35b2061c',
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
      0 => 'fb0d66f89a70cd9e00659e2109a359fcf973e0d8',
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
      0 => '6fd964f4a52319e8a836b8ab285e3b42366fa916',
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
      0 => '79d9c96e603d95ff0b1be7971d844bb5951c5004',
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
      0 => '7067d8a73db765ce7f5bd76518238266995a44ff',
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
      0 => '9cb59c3337087acb066d8182ded2b33aab2a9f92',
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
      0 => '4d606f60775c2c85d080334db147c4ddfb04f8da',
      1 => 
      array (
        0 => 'tests\\unit\\models\\posttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\models\\correctlyinitializeswithvaliddata',
        1 => 'tests\\unit\\models\\handlesnullablefieldscorrectly',
        2 => 'tests\\unit\\models\\setsdefaultvaluescorrectly',
        3 => 'tests\\unit\\models\\storesrawhtmlintitleandcontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Exceptions/PostNotFoundExceptionTest.php' => 
    array (
      0 => 'e391d3f06b4208cc29fedf05c603945a49ba0379',
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
      0 => '703d797d0c214eb7d5024ab1fd1496755979073a',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\createpostdtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\setup',
        1 => 'tests\\unit\\dtos\\post\\testcancreatedtowithvaliddata',
        2 => 'tests\\unit\\dtos\\post\\testcancreatedtowithminimaldata',
        3 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpublishdate',
        4 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpinnedpost',
        5 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingtitle',
        6 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptytitle',
        7 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionfortitletoolong',
        8 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionfortitlewithonlywhitespace',
        9 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingcontent',
        10 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptycontent',
        11 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforcontentwithonlywhitespace',
        12 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvaliduserid',
        13 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissinguserid',
        14 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidip',
        15 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionformissingip',
        16 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidstatus',
        17 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidpublishdate',
        18 => 'tests\\unit\\dtos\\post\\testacceptsvalidipv6address',
        19 => 'tests\\unit\\dtos\\post\\testacceptsallvalidpoststatuses',
        20 => 'tests\\unit\\dtos\\post\\testhandlesbooleanvalues',
        21 => 'tests\\unit\\dtos\\post\\testtoarrayreturnscorrectformat',
        22 => 'tests\\unit\\dtos\\post\\testjsonserializationworks',
        23 => 'tests\\unit\\dtos\\post\\testacceptsrfc3339datetimeformats',
        24 => 'tests\\unit\\dtos\\post\\testtrimstitleandcontent',
        25 => 'tests\\unit\\dtos\\post\\testhandlesemptypublishdate',
        26 => 'tests\\unit\\dtos\\post\\testvalidatesunicodecontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Http/ApiResponseTest.php' => 
    array (
      0 => '934be51b4dcc614bbf594b8625c54f48261c6266',
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
      0 => '69514de4a65f08d70f2a27f46086cd13594a3349',
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
      0 => 'f9be92b7abecc32ad7e4034aceaeda78435e32ef',
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
      0 => '9645078e70c7d667259e38a2b54abbd3238ac1b7',
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
      0 => '7ba2412e7465fa23cce87c215462f995904012ca',
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
      0 => 'fc4d59dbfa61ea6330fdf1bd885ec3a0d9fca2c1',
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
      0 => 'e3659de07a0c9ae0b571fa9d348a3ebc04aeea6a',
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
      0 => 'cc5e8202cb855560ee68cced864fdf59e41f0abc',
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
      0 => 'db1240b19a345ebb2e5a92128f9d1d0a251564eb',
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
      0 => 'e1903d72815deedd8d627f2c87b49693165facfc',
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
      0 => 'f2a418ab863baf02f4cc3a0d22a08ab698b07d6f',
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
      0 => '3c585dcd8f87be07b37611620dd6f36bc4d5ab7a',
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
        7 => 'tests\\unit\\services\\recursiveremovedirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/IpServiceTest.php' => 
    array (
      0 => '6b9c1119410f076276d8d6a9b8a7c5bb00bd021f',
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
      0 => '55230e729c0612b58e8df9f583e56fc94f7d5f76',
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
      0 => '975feab8cea4804fd94b6e3d30124a756480e1f0',
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
      0 => '3e7398761b31d57b413322c046b81f0a64e24578',
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
        5 => 'tests\\security\\shouldsanitizesearchinput',
        6 => 'tests\\security\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/XssPreventionTest.php' => 
    array (
      0 => 'eae252f45b41b4f62fb62e185a108dfcf141e7e8',
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
      0 => 'a24d5094446a39c94b5c2096d1b6160c47345986',
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
        6 => 'tests\\security\\shouldacceptvalidfiles',
        7 => 'tests\\security\\createuploadedfilemock',
        8 => 'tests\\security\\teardown',
        9 => 'tests\\security\\removedirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Security/PasswordHashingTest.php' => 
    array (
      0 => '051108c88dd0974db31e0e08e2a26cfbfa00e5fb',
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
    '/var/www/html/tests/Integration/RateLimitTest.php' => 
    array (
      0 => 'b9b2bc45c51d2f1246dbc126403f5030cd8371af',
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
        5 => 'tests\\integration\\shouldincrementcountercorrectly',
        6 => 'tests\\integration\\shouldhandlemaxattemptsreached',
        7 => 'tests\\integration\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/AuthControllerTest.php' => 
    array (
      0 => '37d2fb2cd2426c740e3f6c3f2663f1198f300be2',
      1 => 
      array (
        0 => 'tests\\integration\\authcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\registerusersuccessfully',
        3 => 'tests\\integration\\returnvalidationerrorsforinvalidregistrationdata',
        4 => 'tests\\integration\\loginusersuccessfully',
        5 => 'tests\\integration\\returnerrorforinvalidlogin',
        6 => 'tests\\integration\\logoutusersuccessfully',
        7 => 'tests\\integration\\getuserinfosuccessfully',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/Repositories/PostRepositoryTest.php' => 
    array (
      0 => 'e0e2ace5a8c4aeb8da68f26ee0ea455aeb6c73d6',
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
      0 => 'b93f435d7c98e2bdbbb1f37f81295943a0649f24',
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
      0 => 'ce5f507046afc53c6056087089f1da68eb3cabc8',
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
      0 => '59546ed67acafb919db54b680163fa716c90e350',
      1 => 
      array (
        0 => 'tests\\integration\\http\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\http\\setup',
        1 => 'tests\\integration\\http\\setupresponsemocks',
        2 => 'tests\\integration\\http\\testgetpostsreturnssuccessresponse',
        3 => 'tests\\integration\\http\\testgetpostswithpaginationparameters',
        4 => 'tests\\integration\\http\\testgetpostswithsearchfilter',
        5 => 'tests\\integration\\http\\testgetpostswithstatusfilter',
        6 => 'tests\\integration\\http\\testgetpostswithinvalidlimitreturnsposts',
        7 => 'tests\\integration\\http\\testcreatepostwithvaliddata',
        8 => 'tests\\integration\\http\\testcreatepostwithinvalidjsonreturnserror',
        9 => 'tests\\integration\\http\\testcreatepostwithmissingrequiredfields',
        10 => 'tests\\integration\\http\\testgetpostbyidreturnssuccess',
        11 => 'tests\\integration\\http\\testgetnonexistentpostreturnsnotfound',
        12 => 'tests\\integration\\http\\testgetpostwithinvalididreturnserror',
        13 => 'tests\\integration\\http\\testupdatepostwithvaliddata',
        14 => 'tests\\integration\\http\\testupdatenonexistentpostreturnsnotfound',
        15 => 'tests\\integration\\http\\testdeletepost',
        16 => 'tests\\integration\\http\\testdeletenonexistentpostreturnsnotfound',
        17 => 'tests\\integration\\http\\testtogglepostpin',
        18 => 'tests\\integration\\http\\testtogglepostpinwithinvaliddata',
        19 => 'tests\\integration\\http\\testapiresponsestructureconsistency',
        20 => 'tests\\integration\\http\\testhealthendpoint',
        21 => 'tests\\integration\\http\\teardown',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/FileSystemBackupTest.php' => 
    array (
      0 => '79e1ec6f61614cbbfb2c96f79e77843c8c5e0d67',
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
      0 => 'f3acb539e1bac76b5da0d38aa15350d2bfd861f1',
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
      0 => '0227c3f4d59b338a526a73150345f93c892dee1a',
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
      0 => 'd8a25c053c36f91a66dd5a023541779a9fc679da',
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
    '/var/www/html/tests/Unit/DTOs/Post/UpdatePostDTOTest.php' => 
    array (
      0 => 'f5b68547762defd63335e83cd592d5505d6235c3',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\updatepostdtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\post\\setup',
        1 => 'tests\\unit\\dtos\\post\\testcancreatedtowithfullupdate',
        2 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpartialupdate',
        3 => 'tests\\unit\\dtos\\post\\testcancreatedtowithcontentonlyupdate',
        4 => 'tests\\unit\\dtos\\post\\testcancreatedtowithstatusonlyupdate',
        5 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpinnedonlyupdate',
        6 => 'tests\\unit\\dtos\\post\\testcancreatedtowithpublishdateonlyupdate',
        7 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithnodata',
        8 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithonlynullvalues',
        9 => 'tests\\unit\\dtos\\post\\testcancreateemptydtowithonlyemptystrings',
        10 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidtitle',
        11 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptytitlecontent',
        12 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforemptycontentcontent',
        13 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidstatus',
        14 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidpublishdate',
        15 => 'tests\\unit\\dtos\\post\\testshouldthrowexceptionforinvalidbooleanvalue',
        16 => 'tests\\unit\\dtos\\post\\testacceptsallvalidpoststatuses',
        17 => 'tests\\unit\\dtos\\post\\testhandlesbooleanvalues',
        18 => 'tests\\unit\\dtos\\post\\testtoarrayreturnsonlychangedfields',
        19 => 'tests\\unit\\dtos\\post\\testtoarrayreturnsemptyarraywhennochanges',
        20 => 'tests\\unit\\dtos\\post\\testtoarraywithallfields',
        21 => 'tests\\unit\\dtos\\post\\testhaschangesreturnstruewhendataexists',
        22 => 'tests\\unit\\dtos\\post\\testhaschangesreturnsfalsewhennodata',
        23 => 'tests\\unit\\dtos\\post\\testjsonserializationworks',
        24 => 'tests\\unit\\dtos\\post\\testjsonserializationwithemptydto',
        25 => 'tests\\unit\\dtos\\post\\testacceptsvalidrfc3339dateformats',
        26 => 'tests\\unit\\dtos\\post\\testhandleswhitespaceinstringfields',
        27 => 'tests\\unit\\dtos\\post\\testgetupdatedfields',
        28 => 'tests\\unit\\dtos\\post\\testhasupdatedfield',
        29 => 'tests\\unit\\dtos\\post\\testhandlesemptypublishdate',
        30 => 'tests\\unit\\dtos\\post\\testvalidatesunicodecontent',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/BaseDTOTest.php' => 
    array (
      0 => '54872a8719c4255f43f792cf3eabb74262684169',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\basedtotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\setup',
        1 => 'tests\\unit\\dtos\\teardown',
        2 => 'tests\\unit\\dtos\\createtestdto',
        3 => 'tests\\unit\\dtos\\getvalidationrules',
        4 => 'tests\\unit\\dtos\\toarray',
        5 => 'tests\\unit\\dtos\\testvalidate',
        6 => 'tests\\unit\\dtos\\testgetstring',
        7 => 'tests\\unit\\dtos\\testgetint',
        8 => 'tests\\unit\\dtos\\testgetbool',
        9 => 'tests\\unit\\dtos\\testgetvalue',
        10 => 'tests\\unit\\dtos\\testgetvalidationrules',
        11 => 'tests\\unit\\dtos\\testconstructoracceptsvalidator',
        12 => 'tests\\unit\\dtos\\testtoarrayisimplemented',
        13 => 'tests\\unit\\dtos\\testjsonserializeusestoarray',
        14 => 'tests\\unit\\dtos\\testvalidatecallsvalidatorwithcorrectdata',
        15 => 'tests\\unit\\dtos\\testvalidatethrowsexceptiononvalidationfailure',
        16 => 'tests\\unit\\dtos\\testgetstringreturnscorrectvalue',
        17 => 'tests\\unit\\dtos\\testgetintreturnscorrectvalue',
        18 => 'tests\\unit\\dtos\\testgetboolreturnscorrectvalue',
        19 => 'tests\\unit\\dtos\\testgetvaluereturnscorrectvalue',
        20 => 'tests\\unit\\dtos\\testvalidationrulesareabstract',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/DTOs/DTOValidationTest.php' => 
    array (
      0 => '27c41f98b0a8790b06929b93c580296a747b9bea',
      1 => 
      array (
        0 => 'tests\\unit\\dtos\\dtovalidationtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\dtos\\setup',
        1 => 'tests\\unit\\dtos\\test_create_post_dto_validation_scenarios',
        2 => 'tests\\unit\\dtos\\test_create_post_dto_title_validation',
        3 => 'tests\\unit\\dtos\\test_create_post_dto_title_length_limits',
        4 => 'tests\\unit\\dtos\\test_create_post_dto_content_validation',
        5 => 'tests\\unit\\dtos\\test_create_post_dto_user_validation',
        6 => 'tests\\unit\\dtos\\test_create_post_dto_ip_validation',
        7 => 'tests\\unit\\dtos\\test_update_post_dto_partial_validation',
        8 => 'tests\\unit\\dtos\\test_update_post_dto_field_validation',
        9 => 'tests\\unit\\dtos\\test_create_attachment_dto_validation',
        10 => 'tests\\unit\\dtos\\test_create_attachment_dto_file_validation',
        11 => 'tests\\unit\\dtos\\test_register_user_dto_validation',
        12 => 'tests\\unit\\dtos\\test_register_user_dto_username_validation',
        13 => 'tests\\unit\\dtos\\test_register_user_dto_email_validation',
        14 => 'tests\\unit\\dtos\\test_register_user_dto_password_validation',
        15 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_validation',
        16 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_ip_validation',
        17 => 'tests\\unit\\dtos\\test_create_ip_rule_dto_rule_type_validation',
        18 => 'tests\\unit\\dtos\\test_dto_data_sanitization',
        19 => 'tests\\unit\\dtos\\test_dto_validation_performance',
        20 => 'tests\\unit\\dtos\\test_dto_serialization',
        21 => 'tests\\unit\\dtos\\test_dto_edge_cases',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidationExceptionTest.php' => 
    array (
      0 => 'ec349770e9350c88a24c5c02e866acf40a279d47',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\test_create_from_validation_result',
        1 => 'tests\\unit\\validation\\test_create_with_custom_message',
        2 => 'tests\\unit\\validation\\test_empty_validation_result_default_message',
        3 => 'tests\\unit\\validation\\test_create_from_errors',
        4 => 'tests\\unit\\validation\\test_create_from_errors_with_custom_message',
        5 => 'tests\\unit\\validation\\test_create_from_single_error',
        6 => 'tests\\unit\\validation\\test_create_from_single_error_without_rule',
        7 => 'tests\\unit\\validation\\test_get_field_errors',
        8 => 'tests\\unit\\validation\\test_has_field_errors',
        9 => 'tests\\unit\\validation\\test_get_first_error',
        10 => 'tests\\unit\\validation\\test_get_first_field_error',
        11 => 'tests\\unit\\validation\\test_get_all_errors',
        12 => 'tests\\unit\\validation\\test_get_failed_rules',
        13 => 'tests\\unit\\validation\\test_to_api_response',
        14 => 'tests\\unit\\validation\\test_to_debug_array',
        15 => 'tests\\unit\\validation\\test_error_statistics',
        16 => 'tests\\unit\\validation\\test_has_failed_rule',
        17 => 'tests\\unit\\validation\\test_has_field_failed_rule',
        18 => 'tests\\unit\\validation\\test_exception_chaining',
        19 => 'tests\\unit\\validation\\test_json_serialization',
        20 => 'tests\\unit\\validation\\test_performance_with_many_errors',
        21 => 'tests\\unit\\validation\\test_internationalization_support',
        22 => 'tests\\unit\\validation\\test_edge_cases_with_empty_errors',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidatorTest.php' => 
    array (
      0 => '71d5b9ff4a2e5377c6a5a4e7ac8a8a6610c6fa11',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validatortest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\setup',
        1 => 'tests\\unit\\validation\\test_required_rule',
        2 => 'tests\\unit\\validation\\test_string_rule',
        3 => 'tests\\unit\\validation\\test_integer_rule',
        4 => 'tests\\unit\\validation\\test_numeric_rule',
        5 => 'tests\\unit\\validation\\test_boolean_rule',
        6 => 'tests\\unit\\validation\\test_email_rule',
        7 => 'tests\\unit\\validation\\test_url_rule',
        8 => 'tests\\unit\\validation\\test_ip_rule',
        9 => 'tests\\unit\\validation\\test_min_rule',
        10 => 'tests\\unit\\validation\\test_max_rule',
        11 => 'tests\\unit\\validation\\test_between_rule',
        12 => 'tests\\unit\\validation\\test_in_rule',
        13 => 'tests\\unit\\validation\\test_not_in_rule',
        14 => 'tests\\unit\\validation\\test_min_length_rule',
        15 => 'tests\\unit\\validation\\test_max_length_rule',
        16 => 'tests\\unit\\validation\\test_length_rule',
        17 => 'tests\\unit\\validation\\test_regex_rule',
        18 => 'tests\\unit\\validation\\test_alpha_rule',
        19 => 'tests\\unit\\validation\\test_alpha_num_rule',
        20 => 'tests\\unit\\validation\\test_alpha_dash_rule',
        21 => 'tests\\unit\\validation\\test_multiple_rules',
        22 => 'tests\\unit\\validation\\test_add_custom_rule',
        23 => 'tests\\unit\\validation\\test_custom_rule_with_parameters',
        24 => 'tests\\unit\\validation\\test_custom_error_messages',
        25 => 'tests\\unit\\validation\\test_validate_or_fail',
        26 => 'tests\\unit\\validation\\test_check_rule_method',
        27 => 'tests\\unit\\validation\\test_stop_on_first_failure',
        28 => 'tests\\unit\\validation\\test_validator_performance',
        29 => 'tests\\unit\\validation\\test_memory_leak',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Validation/ValidationResultTest.php' => 
    array (
      0 => '3d74aa59e5aabd8b7ea18f30fcb29347b67b0530',
      1 => 
      array (
        0 => 'tests\\unit\\validation\\validationresulttest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\validation\\test_create_success_result',
        1 => 'tests\\unit\\validation\\test_create_failure_result',
        2 => 'tests\\unit\\validation\\test_constructor',
        3 => 'tests\\unit\\validation\\test_get_field_errors',
        4 => 'tests\\unit\\validation\\test_has_field_errors',
        5 => 'tests\\unit\\validation\\test_get_first_error',
        6 => 'tests\\unit\\validation\\test_get_first_field_error',
        7 => 'tests\\unit\\validation\\test_get_all_errors',
        8 => 'tests\\unit\\validation\\test_error_count',
        9 => 'tests\\unit\\validation\\test_get_failed_rules',
        10 => 'tests\\unit\\validation\\test_add_error',
        11 => 'tests\\unit\\validation\\test_add_failed_rule',
        12 => 'tests\\unit\\validation\\test_validated_data_access',
        13 => 'tests\\unit\\validation\\test_merge_results',
        14 => 'tests\\unit\\validation\\test_to_array',
        15 => 'tests\\unit\\validation\\test_json_serialization',
        16 => 'tests\\unit\\validation\\test_to_string',
        17 => 'tests\\unit\\validation\\test_empty_results',
        18 => 'tests\\unit\\validation\\test_performance_with_large_data',
        19 => 'tests\\unit\\validation\\test_object_independence',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/PostControllerTest_new.php' => 
    array (
      0 => '7c5e6b39e35bc603af9fc0a8ff16bf94962eb751',
      1 => 
      array (
        0 => 'tests\\integration\\postcontrollertest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\indexshouldreturnpaginatedposts',
        3 => 'tests\\integration\\showshouldreturnpostdetails',
        4 => 'tests\\integration\\storeshouldcreatenewpost',
        5 => 'tests\\integration\\storeshouldreturn400whenvalidationfails',
        6 => 'tests\\integration\\updateshouldmodifyexistingpost',
        7 => 'tests\\integration\\updateshouldreturn404whenpostnotfound',
        8 => 'tests\\integration\\destroyshoulddeletepost',
        9 => 'tests\\integration\\destroyshouldreturn404whenpostnotfound',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DTOs/DTOControllerIntegrationTest.php' => 
    array (
      0 => '9b88870e37f50b052287318a89ed80ea1187f7fa',
      1 => 
      array (
        0 => 'tests\\integration\\dtos\\dtocontrollerintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\dtos\\setup',
        1 => 'tests\\integration\\dtos\\testpostcontrollercreatewithvaliddto',
        2 => 'tests\\integration\\dtos\\testpostcontrollerupdatewithvaliddto',
        3 => 'tests\\integration\\dtos\\testcontrollerhandlesvalidationerrors',
        4 => 'tests\\integration\\dtos\\testdtodatasanitization',
        5 => 'tests\\integration\\dtos\\testdtotypeconversion',
        6 => 'tests\\integration\\dtos\\testdtoinbatchoperations',
        7 => 'tests\\integration\\dtos\\testdtomemoryefficiency',
        8 => 'tests\\integration\\dtos\\testdtoserialization',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DTOs/DTOValidationIntegrationTest.php' => 
    array (
      0 => '7e7d75b3d72a049d4b5188b844fa58d50c596699',
      1 => 
      array (
        0 => 'tests\\integration\\dtos\\dtovalidationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\dtos\\setup',
        1 => 'tests\\integration\\dtos\\testcreatepostdtovalidationintegration',
        2 => 'tests\\integration\\dtos\\testcreatepostdtotitletooshort',
        3 => 'tests\\integration\\dtos\\testcreatepostdtocontenttooshort',
        4 => 'tests\\integration\\dtos\\testupdatepostdtovalidationintegration',
        5 => 'tests\\integration\\dtos\\testcreateattachmentdtovalidationintegration',
        6 => 'tests\\integration\\dtos\\testregisteruserdtovalidationintegration',
        7 => 'tests\\integration\\dtos\\testcreateipruledtovalidationintegration',
        8 => 'tests\\integration\\dtos\\testvalidationerrormessagesinchinese',
        9 => 'tests\\integration\\dtos\\testmultiplefieldvalidationerrors',
        10 => 'tests\\integration\\dtos\\testdtojsonserialization',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/DIValidationIntegrationTest.php' => 
    array (
      0 => '85ebbd57fbd129f6a24b781870039098f1b39905',
      1 => 
      array (
        0 => 'tests\\integration\\divalidationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\test_can_resolve_validator_interface_from_container',
        2 => 'tests\\integration\\test_can_resolve_validator_factory_from_container',
        3 => 'tests\\integration\\test_validator_from_factory_has_chinese_messages',
        4 => 'tests\\integration\\test_container_validator_has_custom_rules',
        5 => 'tests\\integration\\test_validator_factory_create_methods',
        6 => 'tests\\integration\\test_dto_validator_has_password_confirmation_rule',
        7 => 'tests\\integration\\test_container_validator_consistency',
        8 => 'tests\\integration\\test_validator_error_message_localization',
        9 => 'tests\\integration\\test_validator_performance_and_memory',
        10 => 'tests\\integration\\test_simplified_validation_scenarios',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_routing_system.php' => 
    array (
      0 => 'e091ee6e3f571778a0819c9a2581d24d3af5f873',
      1 => 
      array (
        0 => 'mockserverrequest',
        1 => 'mockuri',
      ),
      2 => 
      array (
        0 => '__construct',
        1 => 'getmethod',
        2 => 'geturi',
        3 => '__construct',
        4 => 'getpath',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_route_cache.php' => 
    array (
      0 => 'd766fe630d6a76ddfcee63d7cdb44bd4e4d1c1f6',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'createtestroutes',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_middleware_system.php' => 
    array (
      0 => '46c3efafd9241643364adde9a866d0ec0f580bad',
      1 => 
      array (
        0 => 'loggingmiddleware',
        1 => 'authmiddleware',
      ),
      2 => 
      array (
        0 => 'getmethod',
        1 => 'geturi',
        2 => 'getpath',
        3 => 'withattribute',
        4 => 'getattribute',
        5 => 'getattributes',
        6 => 'getprotocolversion',
        7 => 'withprotocolversion',
        8 => 'getheaders',
        9 => 'hasheader',
        10 => 'getheader',
        11 => 'getheaderline',
        12 => 'withheader',
        13 => 'withaddedheader',
        14 => 'withoutheader',
        15 => 'getbody',
        16 => '__tostring',
        17 => 'withbody',
        18 => 'getrequesttarget',
        19 => 'withrequesttarget',
        20 => 'withmethod',
        21 => 'withuri',
        22 => 'getserverparams',
        23 => 'getcookieparams',
        24 => 'withcookieparams',
        25 => 'getqueryparams',
        26 => 'withqueryparams',
        27 => 'getuploadedfiles',
        28 => 'withuploadedfiles',
        29 => 'getparsedbody',
        30 => 'withparsedbody',
        31 => 'withoutattribute',
        32 => '__construct',
        33 => 'getbody',
        34 => '__construct',
        35 => '__tostring',
        36 => 'getstatuscode',
        37 => 'withstatus',
        38 => 'getreasonphrase',
        39 => 'getprotocolversion',
        40 => 'withprotocolversion',
        41 => 'getheaders',
        42 => 'hasheader',
        43 => 'getheader',
        44 => 'getheaderline',
        45 => 'withheader',
        46 => 'withaddedheader',
        47 => 'withoutheader',
        48 => 'withbody',
        49 => '__construct',
        50 => 'execute',
        51 => '__construct',
        52 => 'execute',
        53 => 'handle',
        54 => 'getbody',
        55 => '__tostring',
        56 => 'getstatuscode',
        57 => 'withstatus',
        58 => 'getreasonphrase',
        59 => 'getprotocolversion',
        60 => 'withprotocolversion',
        61 => 'getheaders',
        62 => 'hasheader',
        63 => 'getheader',
        64 => 'getheaderline',
        65 => 'withheader',
        66 => 'withaddedheader',
        67 => 'withoutheader',
        68 => 'withbody',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_controller_integration.php' => 
    array (
      0 => '85ffc7604c1795c20c262d4e1ece377e0c0a4873',
      1 => 
      array (
        0 => 'mockstream',
        1 => 'mockuri',
        2 => 'mockresponse',
        3 => 'mockrequest',
      ),
      2 => 
      array (
        0 => '__tostring',
        1 => 'close',
        2 => 'detach',
        3 => 'getsize',
        4 => 'tell',
        5 => 'eof',
        6 => 'isseekable',
        7 => 'seek',
        8 => 'rewind',
        9 => 'iswritable',
        10 => 'write',
        11 => 'isreadable',
        12 => 'read',
        13 => 'getcontents',
        14 => 'getmetadata',
        15 => '__construct',
        16 => 'getscheme',
        17 => 'getauthority',
        18 => 'getuserinfo',
        19 => 'gethost',
        20 => 'getport',
        21 => 'getpath',
        22 => 'getquery',
        23 => 'getfragment',
        24 => 'withscheme',
        25 => 'withuserinfo',
        26 => 'withhost',
        27 => 'withport',
        28 => 'withpath',
        29 => 'withquery',
        30 => 'withfragment',
        31 => '__tostring',
        32 => '__construct',
        33 => 'getprotocolversion',
        34 => 'withprotocolversion',
        35 => 'getheaders',
        36 => 'hasheader',
        37 => 'getheader',
        38 => 'getheaderline',
        39 => 'withheader',
        40 => 'withaddedheader',
        41 => 'withoutheader',
        42 => 'getbody',
        43 => 'withbody',
        44 => 'getstatuscode',
        45 => 'withstatus',
        46 => 'getreasonphrase',
        47 => '__construct',
        48 => 'getrequesttarget',
        49 => 'withrequesttarget',
        50 => 'getmethod',
        51 => 'withmethod',
        52 => 'geturi',
        53 => 'withuri',
        54 => 'getprotocolversion',
        55 => 'withprotocolversion',
        56 => 'getheaders',
        57 => 'hasheader',
        58 => 'getheader',
        59 => 'getheaderline',
        60 => 'withheader',
        61 => 'withaddedheader',
        62 => 'withoutheader',
        63 => 'getbody',
        64 => 'withbody',
        65 => 'getserverparams',
        66 => 'getcookieparams',
        67 => 'withcookieparams',
        68 => 'getqueryparams',
        69 => 'withqueryparams',
        70 => 'getuploadedfiles',
        71 => 'withuploadedfiles',
        72 => 'getparsedbody',
        73 => 'withparsedbody',
        74 => 'getattributes',
        75 => 'getattribute',
        76 => 'withattribute',
        77 => 'withoutattribute',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_multiple_routes.php' => 
    array (
      0 => '7d121ab21c629f3d1caa8a2d9f2a7b0740fdcabb',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => '__construct',
        1 => 'getserverparams',
        2 => 'getcookieparams',
        3 => 'withcookieparams',
        4 => 'getqueryparams',
        5 => 'withqueryparams',
        6 => 'getuploadedfiles',
        7 => 'withuploadedfiles',
        8 => 'getparsedbody',
        9 => 'withparsedbody',
        10 => 'getattributes',
        11 => 'getattribute',
        12 => 'withattribute',
        13 => 'withoutattribute',
        14 => 'getrequesttarget',
        15 => 'withrequesttarget',
        16 => 'getmethod',
        17 => 'withmethod',
        18 => 'geturi',
        19 => '__construct',
        20 => '__tostring',
        21 => 'getscheme',
        22 => 'getauthority',
        23 => 'getuserinfo',
        24 => 'gethost',
        25 => 'getport',
        26 => 'getpath',
        27 => 'getquery',
        28 => 'getfragment',
        29 => 'withscheme',
        30 => 'withuserinfo',
        31 => 'withhost',
        32 => 'withport',
        33 => 'withpath',
        34 => 'withquery',
        35 => 'withfragment',
        36 => 'withuri',
        37 => 'getprotocolversion',
        38 => 'withprotocolversion',
        39 => 'getheaders',
        40 => 'hasheader',
        41 => 'getheader',
        42 => 'getheaderline',
        43 => 'withheader',
        44 => 'withaddedheader',
        45 => 'withoutheader',
        46 => 'getbody',
        47 => '__tostring',
        48 => 'close',
        49 => 'detach',
        50 => 'getsize',
        51 => 'tell',
        52 => 'eof',
        53 => 'isseekable',
        54 => 'seek',
        55 => 'rewind',
        56 => 'iswritable',
        57 => 'write',
        58 => 'isreadable',
        59 => 'read',
        60 => 'getcontents',
        61 => 'getmetadata',
        62 => 'withbody',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_di_container.php' => 
    array (
      0 => 'e7cfd954a7b70415cc97b8460004a250e0bec615',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'getserverparams',
        1 => 'getcookieparams',
        2 => 'withcookieparams',
        3 => 'getqueryparams',
        4 => 'withqueryparams',
        5 => 'getuploadedfiles',
        6 => 'withuploadedfiles',
        7 => 'getparsedbody',
        8 => 'withparsedbody',
        9 => 'getattributes',
        10 => 'getattribute',
        11 => 'withattribute',
        12 => 'withoutattribute',
        13 => 'getrequesttarget',
        14 => 'withrequesttarget',
        15 => 'getmethod',
        16 => 'withmethod',
        17 => 'geturi',
        18 => '__construct',
        19 => '__tostring',
        20 => 'getscheme',
        21 => 'getauthority',
        22 => 'getuserinfo',
        23 => 'gethost',
        24 => 'getport',
        25 => 'getpath',
        26 => 'getquery',
        27 => 'getfragment',
        28 => 'withscheme',
        29 => 'withuserinfo',
        30 => 'withhost',
        31 => 'withport',
        32 => 'withpath',
        33 => 'withquery',
        34 => 'withfragment',
        35 => 'withuri',
        36 => 'getprotocolversion',
        37 => 'withprotocolversion',
        38 => 'getheaders',
        39 => 'hasheader',
        40 => 'getheader',
        41 => 'getheaderline',
        42 => 'withheader',
        43 => 'withaddedheader',
        44 => 'withoutheader',
        45 => 'getbody',
        46 => '__tostring',
        47 => 'close',
        48 => 'detach',
        49 => 'getsize',
        50 => 'tell',
        51 => 'eof',
        52 => 'isseekable',
        53 => 'seek',
        54 => 'rewind',
        55 => 'iswritable',
        56 => 'write',
        57 => 'isreadable',
        58 => 'read',
        59 => 'getcontents',
        60 => 'getmetadata',
        61 => 'withbody',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_controller_simple.php' => 
    array (
      0 => '40bc113a14a1762e6cbb7054ec535742177c3067',
      1 => 
      array (
        0 => 'mockstream',
        1 => 'mockresponse',
        2 => 'mockrequest',
      ),
      2 => 
      array (
        0 => '__tostring',
        1 => 'close',
        2 => 'detach',
        3 => 'getsize',
        4 => 'tell',
        5 => 'eof',
        6 => 'isseekable',
        7 => 'seek',
        8 => 'rewind',
        9 => 'iswritable',
        10 => 'write',
        11 => 'isreadable',
        12 => 'read',
        13 => 'getcontents',
        14 => 'getmetadata',
        15 => '__construct',
        16 => 'getprotocolversion',
        17 => 'withprotocolversion',
        18 => 'getheaders',
        19 => 'hasheader',
        20 => 'getheader',
        21 => 'getheaderline',
        22 => 'withheader',
        23 => 'withaddedheader',
        24 => 'withoutheader',
        25 => 'getbody',
        26 => 'withbody',
        27 => 'getstatuscode',
        28 => 'withstatus',
        29 => 'getreasonphrase',
        30 => '__construct',
        31 => 'getrequesttarget',
        32 => 'withrequesttarget',
        33 => 'getmethod',
        34 => 'withmethod',
        35 => 'geturi',
        36 => '__construct',
        37 => 'getpath',
        38 => 'withuri',
        39 => 'getprotocolversion',
        40 => 'withprotocolversion',
        41 => 'getheaders',
        42 => 'hasheader',
        43 => 'getheader',
        44 => 'getheaderline',
        45 => 'withheader',
        46 => 'withaddedheader',
        47 => 'withoutheader',
        48 => 'getbody',
        49 => 'withbody',
        50 => 'getserverparams',
        51 => 'getcookieparams',
        52 => 'withcookieparams',
        53 => 'getqueryparams',
        54 => 'withqueryparams',
        55 => 'getuploadedfiles',
        56 => 'withuploadedfiles',
        57 => 'getparsedbody',
        58 => 'withparsedbody',
        59 => 'getattributes',
        60 => 'getattribute',
        61 => 'withattribute',
        62 => 'withoutattribute',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_route_configuration.php' => 
    array (
      0 => 'a5e8838e9f5af394d8dd6637ac9d6b37d23f98be',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'map',
        1 => 'setname',
        2 => 'middleware',
        3 => 'getname',
        4 => 'getmiddlewares',
        5 => 'map',
        6 => 'setname',
        7 => 'middleware',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_route_loader_integration.php' => 
    array (
      0 => 'f1106840acf64cee74659c89ff34aa9f2654ec16',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'getserverparams',
        1 => 'getcookieparams',
        2 => 'withcookieparams',
        3 => 'getqueryparams',
        4 => 'withqueryparams',
        5 => 'getuploadedfiles',
        6 => 'withuploadedfiles',
        7 => 'getparsedbody',
        8 => 'withparsedbody',
        9 => 'getattributes',
        10 => 'getattribute',
        11 => 'withattribute',
        12 => 'withoutattribute',
        13 => 'getrequesttarget',
        14 => 'withrequesttarget',
        15 => 'getmethod',
        16 => 'withmethod',
        17 => 'geturi',
        18 => '__construct',
        19 => '__tostring',
        20 => 'getscheme',
        21 => 'getauthority',
        22 => 'getuserinfo',
        23 => 'gethost',
        24 => 'getport',
        25 => 'getpath',
        26 => 'getquery',
        27 => 'getfragment',
        28 => 'withscheme',
        29 => 'withuserinfo',
        30 => 'withhost',
        31 => 'withport',
        32 => 'withpath',
        33 => 'withquery',
        34 => 'withfragment',
        35 => 'withuri',
        36 => 'getprotocolversion',
        37 => 'withprotocolversion',
        38 => 'getheaders',
        39 => 'hasheader',
        40 => 'getheader',
        41 => 'getheaderline',
        42 => 'withheader',
        43 => 'withaddedheader',
        44 => 'withoutheader',
        45 => 'getbody',
        46 => '__tostring',
        47 => 'close',
        48 => 'detach',
        49 => 'getsize',
        50 => 'tell',
        51 => 'eof',
        52 => 'isseekable',
        53 => 'seek',
        54 => 'rewind',
        55 => 'iswritable',
        56 => 'write',
        57 => 'isreadable',
        58 => 'read',
        59 => 'getcontents',
        60 => 'getmetadata',
        61 => 'withbody',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/manual/test_routing_performance.php' => 
    array (
      0 => '8fb1c1db89ff1c3f1cb5a296b0280daa91fcb911',
      1 => 
      array (
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Services/Security/FileSecurityServiceTest.php' => 
    array (
      0 => 'dfe04f7b9ab0989984012e6d3fc999a3d6d48db2',
      1 => 
      array (
        0 => 'tests\\unit\\services\\security\\filesecurityservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\services\\security\\setup',
        1 => 'tests\\unit\\services\\security\\testvalidateuploadwithvalidfile',
        2 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithemptyfile',
        3 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithoversizedfile',
        4 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithinvalidmimetype',
        5 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithmismatchedextensionandmimetype',
        6 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithpathtraversalinfilename',
        7 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithnullbyteinfilename',
        8 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithforbiddenextension',
        9 => 'tests\\unit\\services\\security\\testvalidateuploadfailswithmaliciouscontent',
        10 => 'tests\\unit\\services\\security\\testgeneratesecurefilename',
        11 => 'tests\\unit\\services\\security\\testgeneratesecurefilenamewithprefix',
        12 => 'tests\\unit\\services\\security\\testsanitizefilename',
        13 => 'tests\\unit\\services\\security\\testisinalloweddirectorywithvalidpath',
        14 => 'tests\\unit\\services\\security\\testisinalloweddirectorywithinvalidpath',
        15 => 'tests\\unit\\services\\security\\testdetectactualmimetypewithnonexistentfile',
        16 => 'tests\\unit\\services\\security\\testdetectactualmimetypewithvalidfile',
        17 => 'tests\\unit\\services\\security\\createuploadedfile',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/InvalidTokenExceptionTest.php' => 
    array (
      0 => 'd5f068e36635e100592f44f28b45a5979e729c4d',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\invalidtokenexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testmalformedfactorymethod',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testsignatureinvalidfactorymethod',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testalgorithmmismatchfactorymethod',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testissuerinvalidfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testaudienceinvalidfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testsubjectmissingfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testclaimsinvalidfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testblacklistedfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testnotbeforefactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/AuthenticationExceptionTest.php' => 
    array (
      0 => 'd6961308354a167955a449d76feb584cf2cabe84',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\authenticationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testattemptid',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetters',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterswithemptycontext',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testretryability',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testrequiresaccountaction',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testinvalidcredentialsfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountlockedfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountdisabledfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testaccountnotverifiedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testtoomanyattemptsfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testusernotfoundfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testpasswordexpiredfactorymethod',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testmissingcredentialsfactorymethod',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testinvalidtokenfactorymethod',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testtokenrequiredfactorymethod',
        23 => 'tests\\unit\\domains\\auth\\exceptions\\testinsufficientprivilegesfactorymethod',
        24 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        25 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        26 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/TokenExpiredExceptionTest.php' => 
    array (
      0 => '829d27d0d876e5931952b2acce70ae67d58887b2',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\tokenexpiredexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testaccesstokenconstruction',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testrefreshtokenconstruction',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testwithoutexpiredtime',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatseconds',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsinglesecond',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatminutes',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsingleminute',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformathours',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsinglehour',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatdays',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testdurationformatsingleday',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessageaccesstoken',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessagerefreshtoken',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextinformation',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testusingcurrenttime',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testaccesstokenfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testrefreshtokenfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testfactorymethodswithdefaults',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testzerodurationedgecase',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testnegativeduration',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/RefreshTokenExceptionTest.php' => 
    array (
      0 => '35a399cbff13a6672043bb866d38fbae1f0149be',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\refreshtokenexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testoperationid',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetters',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterswithemptycontext',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testnotfoundfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testrevokedfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testalreadyusedfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testdevicemismatchfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testusermismatchfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\teststoragefailedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testdeletionfailedfactorymethod',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testrotationfailedfactorymethod',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testlimitexceededfactorymethod',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testfamilymismatchfactorymethod',
        21 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        22 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        23 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/JwtExceptionTest.php' => 
    array (
      0 => '36efb6a87b98826236fde23b7d234b8657d3acef',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\jwtexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\createconcretejwtexception',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\__construct',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithdefaults',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextgetterandsetter',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testaddcontext',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testerrortype',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testgeterrordetails',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testgetuserfriendlymessage',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testtoarray',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testtostringwithoutcontext',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testtostringwithcontext',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexcontextdata',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testinheritancechain',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testcontextimmutability',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Exceptions/TokenGenerationExceptionTest.php' => 
    array (
      0 => '2bed3b19e6e0223e20a906b491231dac7331ec14',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\tokengenerationexceptiontest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructor',
        1 => 'tests\\unit\\domains\\auth\\exceptions\\testconstructorwithreason',
        2 => 'tests\\unit\\domains\\auth\\exceptions\\testcustommessage',
        3 => 'tests\\unit\\domains\\auth\\exceptions\\testadditionalcontext',
        4 => 'tests\\unit\\domains\\auth\\exceptions\\testalldefaultmessages',
        5 => 'tests\\unit\\domains\\auth\\exceptions\\testuserfriendlymessages',
        6 => 'tests\\unit\\domains\\auth\\exceptions\\testisreason',
        7 => 'tests\\unit\\domains\\auth\\exceptions\\testgenerationattemptid',
        8 => 'tests\\unit\\domains\\auth\\exceptions\\testcategorychecks',
        9 => 'tests\\unit\\domains\\auth\\exceptions\\testkeyinvalidfactorymethod',
        10 => 'tests\\unit\\domains\\auth\\exceptions\\testkeymissingfactorymethod',
        11 => 'tests\\unit\\domains\\auth\\exceptions\\testpayloadinvalidfactorymethod',
        12 => 'tests\\unit\\domains\\auth\\exceptions\\testalgorithmunsupportedfactorymethod',
        13 => 'tests\\unit\\domains\\auth\\exceptions\\testclaimsinvalidfactorymethod',
        14 => 'tests\\unit\\domains\\auth\\exceptions\\testsignaturefailedfactorymethod',
        15 => 'tests\\unit\\domains\\auth\\exceptions\\testresourceexhaustedfactorymethod',
        16 => 'tests\\unit\\domains\\auth\\exceptions\\testencodingfailedfactorymethod',
        17 => 'tests\\unit\\domains\\auth\\exceptions\\testfactorymethodswithdefaults',
        18 => 'tests\\unit\\domains\\auth\\exceptions\\testerrordetails',
        19 => 'tests\\unit\\domains\\auth\\exceptions\\testdefaults',
        20 => 'tests\\unit\\domains\\auth\\exceptions\\testcomplexscenario',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/TokenPairTest.php' => 
    array (
      0 => 'f3152bb6edf3106d6bcc6c074adfe4a87e0d6256',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\tokenpairtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithcustomtokentype',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetaccesstokenexpiresin',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetrefreshtokenexpiresin',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokenexpired',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testisrefreshtokenexpired',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testisfullyexpired',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testcanrefresh',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpiry',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpirywithcustomthreshold',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetauthorizationheader',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetauthorizationheaderwithcustomtokentype',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoapiresponse',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoapiresponsewithoutrefreshtoken',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyaccesstoken',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaccesstokenformat',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaccesstokenparts',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyrefreshtoken',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtooshortrefreshtoken',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongrefreshtoken',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptytokentype',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidtokentype',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithaccesstokenexpirationinpast',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithrefreshtokenexpirationinpast',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithrefreshtokenexpirationbeforeaccesstoken',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithexcessivetimeinterval',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testisaccesstokennearexpirywithnegativethreshold',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/DeviceInfoTest.php' => 
    array (
      0 => '1a9ae58433c2ce0e986138a126148b3ab8e3ae1a',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\deviceinfotest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithminimaldata',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithwindowschrome',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithmacsafari',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithandroidmobile',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithipad',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithfirefox',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromuseragentwithcustomdevicename',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarray',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetdevicetype',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfingerprint',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullbrowserinfo',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullbrowserinfowithoutbrowser',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullplatforminfo',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetfullplatforminfowithoutplatform',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testmatches',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtosummary',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testmaskipaddressipv4',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testmaskipaddressipv6',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydeviceid',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdeviceid',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvaliddeviceidcharacters',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydevicename',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdevicename',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyuseragent',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolonguseragent',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyipaddress',
        32 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidipaddress',
        33 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidplatform',
        34 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidbrowser',
        35 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithmultipledevicetypestrue',
        36 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnodevicetypetrue',
        37 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/JwtPayloadTest.php' => 
    array (
      0 => '518750a38d480ac4ab0a918016e04bbeb116c538',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\jwtpayloadtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnotbeforeandcustomclaims',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithvaliddata',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithstringaudience',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithnotbefore',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testisexpired',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactive',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactivewithoutnotbefore',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testhasaudience',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarraywithsingleaudience',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyjti',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongjti',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptysubject',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidsubject',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnonnumericsubject',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyissuer',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyaudience',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidaudiencevalues',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithexpirationbeforeissuedtime',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnotbeforeafterexpiration',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithreservedcustomclaim',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/ValueObjects/TokenBlacklistEntryTest.php' => 
    array (
      0 => '543b36c159f873b95f1fb692a2a1b9aa9d8e353d',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\tokenblacklistentrytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\valueobjects\\setup',
        1 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithvaliddata',
        2 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithminimaldata',
        3 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarray',
        4 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithstringdates',
        5 => 'tests\\unit\\domains\\auth\\valueobjects\\testforuserlogout',
        6 => 'tests\\unit\\domains\\auth\\valueobjects\\testforsecuritybreach',
        7 => 'tests\\unit\\domains\\auth\\valueobjects\\testforsecuritybreachwithinvalidreason',
        8 => 'tests\\unit\\domains\\auth\\valueobjects\\testforaccountchange',
        9 => 'tests\\unit\\domains\\auth\\valueobjects\\testcanbecleanedup',
        10 => 'tests\\unit\\domains\\auth\\valueobjects\\testissecurityrelated',
        11 => 'tests\\unit\\domains\\auth\\valueobjects\\testisuserinitiated',
        12 => 'tests\\unit\\domains\\auth\\valueobjects\\testissysteminitiated',
        13 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetreasondescription',
        14 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetpriority',
        15 => 'tests\\unit\\domains\\auth\\valueobjects\\testisactive',
        16 => 'tests\\unit\\domains\\auth\\valueobjects\\testtoarray',
        17 => 'tests\\unit\\domains\\auth\\valueobjects\\testtodatabasearray',
        18 => 'tests\\unit\\domains\\auth\\valueobjects\\testtodatabasearraywithemptymetadata',
        19 => 'tests\\unit\\domains\\auth\\valueobjects\\testjsonserialize',
        20 => 'tests\\unit\\domains\\auth\\valueobjects\\testequals',
        21 => 'tests\\unit\\domains\\auth\\valueobjects\\testtostring',
        22 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetvalidtokentypes',
        23 => 'tests\\unit\\domains\\auth\\valueobjects\\testgetvalidreasons',
        24 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptyjti',
        25 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongjti',
        26 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidtokentype',
        27 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvalidreason',
        28 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithblacklistedtimetooold',
        29 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithblacklistedtimetoofuture',
        30 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithinvaliduserid',
        31 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithemptydeviceid',
        32 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithtoolongdeviceid',
        33 => 'tests\\unit\\domains\\auth\\valueobjects\\testconstructorwithnonserializablemetadata',
        34 => 'tests\\unit\\domains\\auth\\valueobjects\\testforaccountchangewithinvalidchangetype',
        35 => 'tests\\unit\\domains\\auth\\valueobjects\\testfromarraywithmissingrequiredfield',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Shared/Config/JwtConfigTest.php' => 
    array (
      0 => 'd4b666cf4a046187729b071e804f90f2d12eddc2',
      1 => 
      array (
        0 => 'tests\\unit\\shared\\config\\jwtconfigtest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\shared\\config\\setup',
        1 => 'tests\\unit\\shared\\config\\teardown',
        2 => 'tests\\unit\\shared\\config\\testsuccessfulconfigurationload',
        3 => 'tests\\unit\\shared\\config\\testprivatekeymissing',
        4 => 'tests\\unit\\shared\\config\\testpublickeymissing',
        5 => 'tests\\unit\\shared\\config\\testinvalidprivatekeyformat',
        6 => 'tests\\unit\\shared\\config\\testinvalidpublickeyformat',
        7 => 'tests\\unit\\shared\\config\\testunsupportedalgorithm',
        8 => 'tests\\unit\\shared\\config\\testinvalidaccesstokenttl',
        9 => 'tests\\unit\\shared\\config\\testinvalidrefreshtokenttl',
        10 => 'tests\\unit\\shared\\config\\testrefreshtokenttllessthanaccesstoken',
        11 => 'tests\\unit\\shared\\config\\testdefaultvalues',
        12 => 'tests\\unit\\shared\\config\\testgetbasepayload',
        13 => 'tests\\unit\\shared\\config\\testgetconfigsummary',
        14 => 'tests\\unit\\shared\\config\\testexpirytimestamps',
        15 => 'tests\\unit\\shared\\config\\setvalidenvironmentvariables',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/JwtTokenServiceInterfaceTest.php' => 
    array (
      0 => '2be79d985bdd38abad1e1bb7b3fa509f2573f236',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\jwttokenserviceinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testgeneratetokenpairmethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testvalidateaccesstokenmethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testvalidaterefreshtokenmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testextractpayloadmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testrefreshtokensmethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testrevoketokenmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallusertokensmethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testistokenrevokedmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testgettokenremainingtimemethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testistokennearexpirymethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testistokenownedbymethodsignature',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testistokenfromdevicemethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testgetalgorithmmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testgetaccesstokenttlmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testgetrefreshtokenttlmethodsignature',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/RefreshTokenRepositoryInterfaceTest.php' => 
    array (
      0 => 'b71dba47089e1e1eb5fd9a7f3e2cd240793a27e8',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\refreshtokenrepositoryinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testcreatemethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyjtimethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testfindbytokenhashmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridanddevicemethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testupdatelastusedmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testrevokemethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallbyuseridmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testrevokeallbydevicemethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testdeletemethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testvalidationmethods',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupmethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testcleanuprevokedmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testgetusertokenstatsmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testtokenfamilymethods',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testbatchmethods',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testgettokensnearexpirymethodsignature',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testgetsystemstatsmethodsignature',
        20 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        21 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        22 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Contracts/TokenBlacklistRepositoryInterfaceTest.php' => 
    array (
      0 => '3ca1312a56765674986b42191f1711f45f6ee3bd',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\tokenblacklistrepositoryinterfacetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\contracts\\setup',
        1 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceexists',
        2 => 'tests\\unit\\domains\\auth\\contracts\\testaddtoblacklistmethodsignature',
        3 => 'tests\\unit\\domains\\auth\\contracts\\testisblacklistedmethodsignature',
        4 => 'tests\\unit\\domains\\auth\\contracts\\testistokenhashblacklistedmethodsignature',
        5 => 'tests\\unit\\domains\\auth\\contracts\\testremovefromblacklistmethodsignature',
        6 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyjtimethodsignature',
        7 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyuseridmethodsignature',
        8 => 'tests\\unit\\domains\\auth\\contracts\\testfindbydeviceidmethodsignature',
        9 => 'tests\\unit\\domains\\auth\\contracts\\testfindbyreasonmethodsignature',
        10 => 'tests\\unit\\domains\\auth\\contracts\\testbatchaddtoblacklistmethodsignature',
        11 => 'tests\\unit\\domains\\auth\\contracts\\testbatchisblacklistedmethodsignature',
        12 => 'tests\\unit\\domains\\auth\\contracts\\testbatchremovefromblacklistmethodsignature',
        13 => 'tests\\unit\\domains\\auth\\contracts\\testblacklistallusertokensmethodsignature',
        14 => 'tests\\unit\\domains\\auth\\contracts\\testblacklistalldevicetokensmethodsignature',
        15 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupmethodsignature',
        16 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupexpiredentriesmethodsignature',
        17 => 'tests\\unit\\domains\\auth\\contracts\\testcleanupoldentriesmethodsignature',
        18 => 'tests\\unit\\domains\\auth\\contracts\\testgetblackliststatsmethodsignature',
        19 => 'tests\\unit\\domains\\auth\\contracts\\testgetuserblackliststatsmethodsignature',
        20 => 'tests\\unit\\domains\\auth\\contracts\\testgetrecentblacklistentriesmethodsignature',
        21 => 'tests\\unit\\domains\\auth\\contracts\\testgethighpriorityentriesmethodsignature',
        22 => 'tests\\unit\\domains\\auth\\contracts\\testsearchmethodsignature',
        23 => 'tests\\unit\\domains\\auth\\contracts\\testcountsearchmethodsignature',
        24 => 'tests\\unit\\domains\\auth\\contracts\\testissizeexceededmethodsignature',
        25 => 'tests\\unit\\domains\\auth\\contracts\\testgetsizeinfomethodsignature',
        26 => 'tests\\unit\\domains\\auth\\contracts\\testoptimizemethodsignature',
        27 => 'tests\\unit\\domains\\auth\\contracts\\testallrequiredmethodsexist',
        28 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehascorrectdocumentation',
        29 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceisincorrectnamespace',
        30 => 'tests\\unit\\domains\\auth\\contracts\\testmethodsarepublic',
        31 => 'tests\\unit\\domains\\auth\\contracts\\testinterfaceextendsnootherinterface',
        32 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehasnoconstants',
        33 => 'tests\\unit\\domains\\auth\\contracts\\testinterfacehasnoproperties',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Jwt/FirebaseJwtProviderTest.php' => 
    array (
      0 => '3c56ce1e9d636b4b99d859508a48b06bb76cc1b3',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\jwt\\firebasejwtprovidertest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\jwt\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\jwt\\teardown',
        2 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorsuccessfullyinitializesprovider',
        3 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionforinvalidprivatekey',
        4 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionforinvalidpublickey',
        5 => 'tests\\unit\\infrastructure\\auth\\jwt\\testconstructorthrowsexceptionformismatchedkeys',
        6 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgenerateaccesstokensuccessfully',
        7 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneraterefreshtokensuccessfully',
        8 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgenerateaccesstokenwithcustomttl',
        9 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneraterefreshtokenwithcustomttl',
        10 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidateaccesstokensuccessfully',
        11 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidaterefreshtokensuccessfully',
        12 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforemptytoken',
        13 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionformalformedtoken',
        14 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforexpiredtoken',
        15 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforwrongtokentype',
        16 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidsignature',
        17 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafesuccessfully',
        18 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafethrowsexceptionforemptytoken',
        19 => 'tests\\unit\\infrastructure\\auth\\jwt\\testparsetokenunsafethrowsexceptionforinvalidformat',
        20 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgettokenexpirationsuccessfully',
        21 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgettokenexpirationreturnsnullforinvalidtoken',
        22 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredforvalidtoken',
        23 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredforexpiredtoken',
        24 => 'tests\\unit\\infrastructure\\auth\\jwt\\testistokenexpiredreturnsfalseforinvalidtoken',
        25 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidissuer',
        26 => 'tests\\unit\\infrastructure\\auth\\jwt\\testvalidatetokenthrowsexceptionforinvalidaudience',
        27 => 'tests\\unit\\infrastructure\\auth\\jwt\\testgeneratedtokenshaveuniquejti',
        28 => 'tests\\unit\\infrastructure\\auth\\jwt\\generatetestkeys',
        29 => 'tests\\unit\\infrastructure\\auth\\jwt\\generatealternativekeys',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/JwtTokenServiceSimpleTest.php' => 
    array (
      0 => '19e7ecc6f25838332b5e00eb615330bcdb48a856',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\jwttokenservicesimpletest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_return_token_pair_when_valid_input',
        2 => 'tests\\unit\\domains\\auth\\services\\test_revokeallusertokens_should_delete_all_user_refresh_tokens',
        3 => 'tests\\unit\\domains\\auth\\services\\test_getalgorithm_should_return_rs256',
        4 => 'tests\\unit\\domains\\auth\\services\\test_getaccesstokenttl_should_return_config_value',
        5 => 'tests\\unit\\domains\\auth\\services\\test_getrefreshtokenttl_should_return_config_value',
        6 => 'tests\\unit\\domains\\auth\\services\\test_extractpayload_should_return_payload_without_validation',
        7 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_true_when_owned_by_user',
        8 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_false_when_not_owned_by_user',
        9 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_true_when_from_device',
        10 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_false_when_from_different_device',
        11 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_remaining_seconds',
        12 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_token_invalid',
        13 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_true_when_near_expiry',
        14 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_false_when_not_near_expiry',
        15 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_token_invalid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/JwtTokenServiceTest.php' => 
    array (
      0 => 'f4a3e35c3912ed49be8bf33f2490a19231f02b5a',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\jwttokenservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_return_token_pair_when_valid_input',
        2 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_throw_exception_when_jwt_provider_fails',
        3 => 'tests\\unit\\domains\\auth\\services\\test_generatetokenpair_should_throw_exception_when_repository_fails',
        4 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_return_payload_when_valid_token',
        5 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_throw_exception_when_token_blacklisted',
        6 => 'tests\\unit\\domains\\auth\\services\\test_validateaccesstoken_should_skip_blacklist_check_when_disabled',
        7 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_return_payload_when_valid_token',
        8 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_throw_exception_when_not_found_in_database',
        9 => 'tests\\unit\\domains\\auth\\services\\test_validaterefreshtoken_should_throw_exception_when_revoked',
        10 => 'tests\\unit\\domains\\auth\\services\\test_extractpayload_should_return_payload_without_validation',
        11 => 'tests\\unit\\domains\\auth\\services\\test_refreshtokens_should_return_new_token_pair',
        12 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_add_token_to_blacklist',
        13 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_delete_refresh_token_from_repository',
        14 => 'tests\\unit\\domains\\auth\\services\\test_revoketoken_should_return_false_when_exception_occurs',
        15 => 'tests\\unit\\domains\\auth\\services\\test_revokeallusertokens_should_delete_all_user_refresh_tokens',
        16 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_blacklisted',
        17 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_false_when_not_blacklisted',
        18 => 'tests\\unit\\domains\\auth\\services\\test_istokenrevoked_should_return_true_when_token_invalid',
        19 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_remaining_seconds',
        20 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_expired',
        21 => 'tests\\unit\\domains\\auth\\services\\test_gettokenremainingtime_should_return_zero_when_token_invalid',
        22 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_true_when_near_expiry',
        23 => 'tests\\unit\\domains\\auth\\services\\test_istokennearexpiry_should_return_false_when_not_near_expiry',
        24 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_true_when_owned_by_user',
        25 => 'tests\\unit\\domains\\auth\\services\\test_istokenownedby_should_return_false_when_not_owned_by_user',
        26 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_true_when_from_device',
        27 => 'tests\\unit\\domains\\auth\\services\\test_istokenfromdevice_should_return_false_when_from_different_device',
        28 => 'tests\\unit\\domains\\auth\\services\\test_getalgorithm_should_return_rs256',
        29 => 'tests\\unit\\domains\\auth\\services\\test_getaccesstokenttl_should_return_config_value',
        30 => 'tests\\unit\\domains\\auth\\services\\test_getrefreshtokenttl_should_return_config_value',
        31 => 'tests\\unit\\domains\\auth\\services\\test_createjwtpayloadfromarray_should_create_valid_payload',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Entities/RefreshTokenTest.php' => 
    array (
      0 => 'ce8f4da151f869d4b35f8a3d9e09fc74ad63d914',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\entities\\refreshtokentest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\entities\\setup',
        1 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_create_valid_refresh_token',
        2 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_create_revoked_token',
        3 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_empty',
        4 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_too_short',
        5 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_jti_contains_invalid_characters',
        6 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_user_id_invalid',
        7 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_token_hash_invalid',
        8 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_status_invalid',
        9 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_throw_exception_when_revoked_without_reason',
        10 => 'tests\\unit\\domains\\auth\\entities\\test_isexpired_should_return_true_when_token_expired',
        11 => 'tests\\unit\\domains\\auth\\entities\\test_isexpired_should_return_false_when_token_not_expired',
        12 => 'tests\\unit\\domains\\auth\\entities\\test_isrevoked_should_return_true_when_token_revoked',
        13 => 'tests\\unit\\domains\\auth\\entities\\test_isrevoked_should_return_false_when_token_active',
        14 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_true_when_token_active_and_not_expired',
        15 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_false_when_token_expired',
        16 => 'tests\\unit\\domains\\auth\\entities\\test_isvalid_should_return_false_when_token_revoked',
        17 => 'tests\\unit\\domains\\auth\\entities\\test_canberefreshed_should_return_true_when_token_valid_and_active',
        18 => 'tests\\unit\\domains\\auth\\entities\\test_canberefreshed_should_return_false_when_token_used',
        19 => 'tests\\unit\\domains\\auth\\entities\\test_markasrevoked_should_return_new_revoked_token',
        20 => 'tests\\unit\\domains\\auth\\entities\\test_markasrevoked_should_return_same_token_when_already_revoked',
        21 => 'tests\\unit\\domains\\auth\\entities\\test_markasused_should_return_new_used_token',
        22 => 'tests\\unit\\domains\\auth\\entities\\test_updatelastused_should_return_new_token_with_updated_time',
        23 => 'tests\\unit\\domains\\auth\\entities\\test_equals_should_return_true_when_same_jti',
        24 => 'tests\\unit\\domains\\auth\\entities\\test_equals_should_return_false_when_different_jti',
        25 => 'tests\\unit\\domains\\auth\\entities\\test_belongstouser_should_return_true_when_same_user',
        26 => 'tests\\unit\\domains\\auth\\entities\\test_belongstouser_should_return_false_when_different_user',
        27 => 'tests\\unit\\domains\\auth\\entities\\test_belongstodevice_should_return_true_when_same_device',
        28 => 'tests\\unit\\domains\\auth\\entities\\test_belongstodevice_should_return_false_when_different_device',
        29 => 'tests\\unit\\domains\\auth\\entities\\test_getremainingtime_should_return_correct_seconds',
        30 => 'tests\\unit\\domains\\auth\\entities\\test_getremainingtime_should_return_zero_when_expired',
        31 => 'tests\\unit\\domains\\auth\\entities\\test_isnearexpiry_should_return_true_when_near_expiry',
        32 => 'tests\\unit\\domains\\auth\\entities\\test_isnearexpiry_should_return_false_when_not_near_expiry',
        33 => 'tests\\unit\\domains\\auth\\entities\\test_jsonserialize_should_return_expected_array',
        34 => 'tests\\unit\\domains\\auth\\entities\\test_toarray_should_return_complete_array_with_sensitive_data',
        35 => 'tests\\unit\\domains\\auth\\entities\\test_tostring_should_return_expected_format',
        36 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_accept_minimum_valid_jti_length',
        37 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_accept_maximum_valid_jti_length',
        38 => 'tests\\unit\\domains\\auth\\entities\\test_constructor_should_reject_jti_exceeding_maximum_length',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Repositories/RefreshTokenRepositoryTest.php' => 
    array (
      0 => '83999ba516e91e52d3f9898b2bd8d390bbfbe936',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\refreshtokenrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldreturntrue_whentokencreatedsuccessfully',
        2 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldthrowexception_whendatabasefails',
        3 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcreate_shouldhandleparenttokenjti_whenprovided',
        4 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldreturnarray_whentokenexists',
        5 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldreturnnull_whentokennotfound',
        6 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjti_shouldthrowexception_whendatabasefails',
        7 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldreturnarray_whentokenexists',
        8 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldreturnnull_whentokennotfound',
        9 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldreturnarray_whentokensexist',
        10 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldincludeexpiredtokens_whenrequested',
        11 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid_shouldexcludeexpiredtokens_bydefault',
        12 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuseridanddevice_shouldreturnarray_whentokensexist',
        13 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldreturntrue_whenupdatesuccessful',
        14 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldusecurrenttime_whenlastusedatisnull',
        15 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldreturntrue_whenrevocationsuccessful',
        16 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldusedefaultreason_whenreasonnotprovided',
        17 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldreturnrevokedcount_whensuccessful',
        18 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldexcludejti_whenprovided',
        19 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbydevice_shouldreturnrevokedcount_whensuccessful',
        20 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldreturntrue_whendeletionsuccessful',
        21 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldreturnfalse_whentokennotfound',
        22 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldreturntrue_whentokenisrevoked',
        23 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldreturnfalse_whentokenisactive',
        24 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturntrue_whentokenisexpired',
        25 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturnfalse_whentokenisnotexpired',
        26 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldreturntrue_whentokennotfound',
        27 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisvalid_shouldreturntrue_whentokenisvalidandnotrevoked',
        28 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldreturncleanedcount_whensuccessful',
        29 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldusecurrenttime_whenbeforedateisnull',
        30 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanuprevoked_shouldreturncleanedcount_whensuccessful',
        31 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetusertokenstats_shouldreturnstatsarray_whensuccessful',
        32 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsystemstats_shouldreturnsystemstatsarray_whensuccessful',
        33 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchcreate_shouldreturncreatedcount_whensuccessful',
        34 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchcreate_shouldrollbackandthrowexception_whenfails',
        35 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchrevoke_shouldreturnrevokedcount_whensuccessful',
        36 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchrevoke_shouldreturnzero_whenjtisarrayisempty',
        37 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgettokensnearexpiry_shouldreturnarray_whentokensfound',
        38 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbytokenhash_shouldthrowexception_whendatabasefails',
        39 => 'tests\\unit\\infrastructure\\auth\\repositories\\testupdatelastused_shouldthrowexception_whendatabasefails',
        40 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevoke_shouldthrowexception_whendatabasefails',
        41 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbyuserid_shouldthrowexception_whendatabasefails',
        42 => 'tests\\unit\\infrastructure\\auth\\repositories\\testrevokeallbydevice_shouldthrowexception_whendatabasefails',
        43 => 'tests\\unit\\infrastructure\\auth\\repositories\\testdelete_shouldthrowexception_whendatabasefails',
        44 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisrevoked_shouldthrowexception_whendatabasefails',
        45 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisexpired_shouldthrowexception_whendatabasefails',
        46 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanup_shouldthrowexception_whendatabasefails',
        47 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanuprevoked_shouldthrowexception_whendatabasefails',
        48 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetusertokenstats_shouldthrowexception_whendatabasefails',
        49 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsystemstats_shouldthrowexception_whendatabasefails',
        50 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgettokensnearexpiry_shouldthrowexception_whendatabasefails',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php' => 
    array (
      0 => 'f6474dd1859d020f115a5570a78965a31e1a0aa3',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\authenticationservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\login_成功登入_應該返回登入回應',
        2 => 'tests\\unit\\domains\\auth\\services\\login_使用者不存在_應該拋出認證例外',
        3 => 'tests\\unit\\domains\\auth\\services\\login_使用者已被軟刪除_應該拋出認證例外',
        4 => 'tests\\unit\\domains\\auth\\services\\login_超過最大token數量限制_應該撤銷最舊的token',
        5 => 'tests\\unit\\domains\\auth\\services\\refresh_成功重新整理_應該返回新的token對',
        6 => 'tests\\unit\\domains\\auth\\services\\refresh_無效的refreshtoken_應該拋出認證例外',
        7 => 'tests\\unit\\domains\\auth\\services\\refresh_過期的refreshtoken_應該拋出認證例外',
        8 => 'tests\\unit\\domains\\auth\\services\\logout_單一token登出_應該成功撤銷token',
        9 => 'tests\\unit\\domains\\auth\\services\\logout_全部token登出_應該撤銷使用者所有token',
        10 => 'tests\\unit\\domains\\auth\\services\\logout_沒有refreshtoken_應該仍然成功',
        11 => 'tests\\unit\\domains\\auth\\services\\validateaccesstoken_有效的token_應該返回true',
        12 => 'tests\\unit\\domains\\auth\\services\\validateaccesstoken_無效的token_應該返回false',
        13 => 'tests\\unit\\domains\\auth\\services\\validaterefreshtoken_有效的token_應該返回true',
        14 => 'tests\\unit\\domains\\auth\\services\\validaterefreshtoken_無效的token_應該返回false',
        15 => 'tests\\unit\\domains\\auth\\services\\validaterefreshtoken_token已被撤銷_應該返回false',
        16 => 'tests\\unit\\domains\\auth\\services\\revokerefreshtoken_成功撤銷_應該返回true',
        17 => 'tests\\unit\\domains\\auth\\services\\revokerefreshtoken_撤銷失敗_應該返回false',
        18 => 'tests\\unit\\domains\\auth\\services\\revokeallusertokens_成功撤銷_應該返回撤銷數量',
        19 => 'tests\\unit\\domains\\auth\\services\\revokedevicetokens_成功撤銷_應該返回撤銷數量',
        20 => 'tests\\unit\\domains\\auth\\services\\getusertokenstats_成功取得統計_應該返回統計資料',
        21 => 'tests\\unit\\domains\\auth\\services\\getusertokenstats_發生例外_應該返回預設統計',
        22 => 'tests\\unit\\domains\\auth\\services\\cleanupexpiredtokens_成功清理_應該返回清理數量',
        23 => 'tests\\unit\\domains\\auth\\services\\cleanupexpiredtokens_沒有指定日期_應該使用預設日期',
        24 => 'tests\\unit\\domains\\auth\\services\\cleanuprevokedtokens_成功清理_應該返回清理數量',
        25 => 'tests\\unit\\domains\\auth\\services\\cleanuprevokedtokens_使用預設天數_應該清理30天前的記錄',
        26 => 'tests\\unit\\domains\\auth\\services\\createmocktokenpair',
        27 => 'tests\\unit\\domains\\auth\\services\\createmockjwtpayload',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php' => 
    array (
      0 => '71a24601e8a21ba0de19c16ff59b394efe9f2985',
      1 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\tokenblacklistrepositorytest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\infrastructure\\auth\\repositories\\setup',
        1 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistsuccess',
        2 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistfailure',
        3 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistduplicatekey',
        4 => 'tests\\unit\\infrastructure\\auth\\repositories\\testaddtoblacklistotherexception',
        5 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedtrue',
        6 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedfalse',
        7 => 'tests\\unit\\infrastructure\\auth\\repositories\\testisblacklistedwithexception',
        8 => 'tests\\unit\\infrastructure\\auth\\repositories\\testistokenhashblacklistedtrue',
        9 => 'tests\\unit\\infrastructure\\auth\\repositories\\testremovefromblacklistsuccess',
        10 => 'tests\\unit\\infrastructure\\auth\\repositories\\testremovefromblacklistnotfound',
        11 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjtifound',
        12 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyjtinotfound',
        13 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuserid',
        14 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyuseridwithexception',
        15 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbydeviceid',
        16 => 'tests\\unit\\infrastructure\\auth\\repositories\\testfindbyreason',
        17 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistsuccess',
        18 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistempty',
        19 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistwithfailures',
        20 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchaddtoblacklistwithexception',
        21 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedsuccess',
        22 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedempty',
        23 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchisblacklistedwithexception',
        24 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchremovefromblacklistsuccess',
        25 => 'tests\\unit\\infrastructure\\auth\\repositories\\testbatchremovefromblacklistempty',
        26 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistallusertokenssuccess',
        27 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistallusertokensnotokens',
        28 => 'tests\\unit\\infrastructure\\auth\\repositories\\testblacklistalldevicetokenssuccess',
        29 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupsuccess',
        30 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupwithbeforedate',
        31 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupexpiredentries',
        32 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcleanupoldentries',
        33 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetblackliststatssuccess',
        34 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetuserblackliststatssuccess',
        35 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetrecentblacklistentriessuccess',
        36 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgethighpriorityentriessuccess',
        37 => 'tests\\unit\\infrastructure\\auth\\repositories\\testsearchsuccess',
        38 => 'tests\\unit\\infrastructure\\auth\\repositories\\testcountsearchsuccess',
        39 => 'tests\\unit\\infrastructure\\auth\\repositories\\testissizeexceededtrue',
        40 => 'tests\\unit\\infrastructure\\auth\\repositories\\testissizeexceededfalse',
        41 => 'tests\\unit\\infrastructure\\auth\\repositories\\testgetsizeinfosuccess',
        42 => 'tests\\unit\\infrastructure\\auth\\repositories\\testoptimizesuccess',
        43 => 'tests\\unit\\infrastructure\\auth\\repositories\\testoptimizewithexception',
        44 => 'tests\\unit\\infrastructure\\auth\\repositories\\createsampleentry',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/TokenBlacklistServiceTest.php' => 
    array (
      0 => 'c88951a72b2baca0e370537fb1703c197256eecf',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\tokenblacklistservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokensuccessfully',
        3 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithhighpriorityreason',
        4 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithinvalidtokentype',
        5 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenwithinvalidreason',
        6 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenrepositoryfailure',
        7 => 'tests\\unit\\domains\\auth\\services\\testblacklisttokenrepositoryexception',
        8 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedsuccessfully',
        9 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedwithemptyjti',
        10 => 'tests\\unit\\domains\\auth\\services\\testistokenblacklistedwithrepositoryexception',
        11 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistsuccessfully',
        12 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithemptyarray',
        13 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithemptyjtis',
        14 => 'tests\\unit\\domains\\auth\\services\\testbatchcheckblacklistwithrepositoryexception',
        15 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenssuccessfully',
        16 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithinvaliduserid',
        17 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithinvalidreason',
        18 => 'tests\\unit\\domains\\auth\\services\\testblacklistusertokenswithrepositoryexception',
        19 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenssuccessfully',
        20 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenswithemptydeviceid',
        21 => 'tests\\unit\\domains\\auth\\services\\testblacklistdevicetokenswithrepositoryexception',
        22 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistsuccessfully',
        23 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistwithemptyjti',
        24 => 'tests\\unit\\domains\\auth\\services\\testremovefromblacklistwithrepositoryexception',
        25 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistsuccessfully',
        26 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistwithemptyarray',
        27 => 'tests\\unit\\domains\\auth\\services\\testbatchremovefromblacklistwithrepositoryexception',
        28 => 'tests\\unit\\domains\\auth\\services\\testautocleanupsuccessfully',
        29 => 'tests\\unit\\domains\\auth\\services\\testautocleanupwithrepositoryexception',
        30 => 'tests\\unit\\domains\\auth\\services\\testgetstatisticssuccessfully',
        31 => 'tests\\unit\\domains\\auth\\services\\testgetstatisticswithrepositoryexception',
        32 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticssuccessfully',
        33 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticswithinvaliduserid',
        34 => 'tests\\unit\\domains\\auth\\services\\testgetuserstatisticswithrepositoryexception',
        35 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentriessuccessfully',
        36 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithnegativeoffset',
        37 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithzerolimit',
        38 => 'tests\\unit\\domains\\auth\\services\\testsearchblacklistentrieswithrepositoryexception',
        39 => 'tests\\unit\\domains\\auth\\services\\testgetrecenthighpriorityentriessuccessfully',
        40 => 'tests\\unit\\domains\\auth\\services\\testgetrecenthighpriorityentrieswithrepositoryexception',
        41 => 'tests\\unit\\domains\\auth\\services\\testoptimizesuccessfully',
        42 => 'tests\\unit\\domains\\auth\\services\\testoptimizewithrepositoryexception',
        43 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatushealthy',
        44 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatusunhealthywithrecommendations',
        45 => 'tests\\unit\\domains\\auth\\services\\testgethealthstatuswithrepositoryexception',
        46 => 'tests\\unit\\domains\\auth\\services\\testservicewithoutlogger',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtAuthenticationIntegrationTest.php' => 
    array (
      0 => '34b6eb51b8855fe256e9f948f2aece0708438548',
      1 => 
      array (
        0 => 'tests\\integration\\jwtauthenticationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\teardown',
        2 => 'tests\\integration\\canperformcompleteloginflow',
        3 => 'tests\\integration\\canrefreshtokenssuccessfully',
        4 => 'tests\\integration\\canlogoutandblacklisttokens',
        5 => 'tests\\integration\\canmanagemultipledevicelogins',
        6 => 'tests\\integration\\canhandleinvalidcredentials',
        7 => 'tests\\integration\\cancleanupexpiredblacklistentries',
        8 => 'tests\\integration\\canperformhealthcheck',
        9 => 'tests\\integration\\createjwttokenservice',
        10 => 'tests\\integration\\setupuserrepositorymock',
        11 => 'tests\\integration\\createtestuser',
        12 => 'tests\\integration\\generatetestprivatekey',
        13 => 'tests\\integration\\generatetestpublickey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtAuthenticationIntegrationTest_simple.php' => 
    array (
      0 => '8583b25c1e5c75d3c9f8b1952558caf51e76565a',
      1 => 
      array (
        0 => 'tests\\integration\\jwtauthenticationintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\canmanagerefreshtokens',
        2 => 'tests\\integration\\canmanagetokenblacklist',
        3 => 'tests\\integration\\canusetokenblacklistservice',
        4 => 'tests\\integration\\canautocleanupexpiredentries',
        5 => 'tests\\integration\\canperformbatchoperations',
        6 => 'tests\\integration\\createtestuser',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Integration/JwtTokenBlacklistIntegrationTest.php' => 
    array (
      0 => '9c0db7757d55b7851c826d9116a14282bd40c6ec',
      1 => 
      array (
        0 => 'tests\\integration\\jwttokenblacklistintegrationtest',
      ),
      2 => 
      array (
        0 => 'tests\\integration\\setup',
        1 => 'tests\\integration\\testbasicblacklistintegration',
        2 => 'tests\\integration\\testserviceblacklistoperations',
        3 => 'tests\\integration\\testbatchoperationsintegration',
        4 => 'tests\\integration\\teststatisticsintegration',
        5 => 'tests\\integration\\testautocleanupintegration',
        6 => 'tests\\integration\\testqueryfunctionsintegration',
        7 => 'tests\\integration\\testerrorhandlingintegration',
        8 => 'tests\\integration\\createtokenblacklisttable',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/AuthServiceTest.php' => 
    array (
      0 => '45f7239cebc46954691fff860b356d26baa1437f',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\authservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\test_register_traditional_mode_without_jwt',
        3 => 'tests\\unit\\domains\\auth\\services\\test_register_jwt_mode_with_tokens',
        4 => 'tests\\unit\\domains\\auth\\services\\test_register_jwt_mode_token_generation_failure',
        5 => 'tests\\unit\\domains\\auth\\services\\test_login_traditional_mode_success',
        6 => 'tests\\unit\\domains\\auth\\services\\test_login_jwt_mode_success',
        7 => 'tests\\unit\\domains\\auth\\services\\test_login_user_not_found',
        8 => 'tests\\unit\\domains\\auth\\services\\test_login_user_disabled',
        9 => 'tests\\unit\\domains\\auth\\services\\test_login_invalid_password',
        10 => 'tests\\unit\\domains\\auth\\services\\test_logout_traditional_mode',
        11 => 'tests\\unit\\domains\\auth\\services\\test_logout_jwt_mode_success',
        12 => 'tests\\unit\\domains\\auth\\services\\test_logout_jwt_mode_revocation_failure',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/tests/Unit/Domains/Auth/Services/RefreshTokenServiceTest.php' => 
    array (
      0 => '24bfe5955becdbc39b4cae33648605a92fcea5be',
      1 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\refreshtokenservicetest',
      ),
      2 => 
      array (
        0 => 'tests\\unit\\domains\\auth\\services\\setup',
        1 => 'tests\\unit\\domains\\auth\\services\\teardown',
        2 => 'tests\\unit\\domains\\auth\\services\\testservicecanbeinstantiated',
        3 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokenssuccess',
        4 => 'tests\\unit\\domains\\auth\\services\\testcleanupexpiredtokensexception',
        5 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstatssuccess',
        6 => 'tests\\unit\\domains\\auth\\services\\testgetusertokenstatsexception',
        7 => 'tests\\unit\\domains\\auth\\services\\testrevoketokensuccess',
        8 => 'tests\\unit\\domains\\auth\\services\\testrevoketokenexception',
      ),
      3 => 
      array (
      ),
    ),
  ),
));