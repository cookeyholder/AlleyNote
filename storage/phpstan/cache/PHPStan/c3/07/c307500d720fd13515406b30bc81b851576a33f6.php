<?php declare(strict_types = 1);

// odsl-/var/www/html/src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/src/Middleware/AuthorizationMiddleware.php' => 
    array (
      0 => 'd7ee53bf47cf5f636f4f608c669c997e7ed025ea',
      1 => 
      array (
        0 => 'app\\middleware\\authorizationmiddleware',
      ),
      2 => 
      array (
        0 => 'app\\middleware\\__construct',
        1 => 'app\\middleware\\checkpermission',
        2 => 'app\\middleware\\requirepermission',
        3 => 'app\\middleware\\requirerole',
        4 => 'app\\middleware\\extractresourcefrompath',
        5 => 'app\\middleware\\extractactionfrommethod',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Middleware/RateLimitMiddleware.php' => 
    array (
      0 => '93bcbba1aa2dda98d50e18e912e04d256f92b005',
      1 => 
      array (
        0 => 'app\\middleware\\ratelimitmiddleware',
      ),
      2 => 
      array (
        0 => 'app\\middleware\\__construct',
        1 => 'app\\middleware\\process',
        2 => 'app\\middleware\\determineaction',
        3 => 'app\\middleware\\getuserid',
        4 => 'app\\middleware\\createratelimitresponse',
        5 => 'app\\middleware\\addratelimitheaders',
        6 => 'app\\middleware\\generateratelimithtml',
        7 => 'app\\middleware\\getdefaultconfig',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Database/DatabaseConnection.php' => 
    array (
      0 => 'c2d9122e6135f5789aeb5194c81e131d4315826a',
      1 => 
      array (
        0 => 'app\\database\\databaseconnection',
      ),
      2 => 
      array (
        0 => 'app\\database\\getinstance',
        1 => 'app\\database\\setinstance',
        2 => 'app\\database\\reset',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Cache/CacheManager.php' => 
    array (
      0 => '107a1baf58028404052aa628a629fbb65a605ae6',
      1 => 
      array (
        0 => 'app\\cache\\cachemanager',
      ),
      2 => 
      array (
        0 => 'app\\cache\\__construct',
        1 => 'app\\cache\\get',
        2 => 'app\\cache\\set',
        3 => 'app\\cache\\has',
        4 => 'app\\cache\\delete',
        5 => 'app\\cache\\clear',
        6 => 'app\\cache\\remember',
        7 => 'app\\cache\\rememberforever',
        8 => 'app\\cache\\many',
        9 => 'app\\cache\\putmany',
        10 => 'app\\cache\\deletepattern',
        11 => 'app\\cache\\increment',
        12 => 'app\\cache\\decrement',
        13 => 'app\\cache\\getstats',
        14 => 'app\\cache\\getmemoryusage',
        15 => 'app\\cache\\cleanup',
        16 => 'app\\cache\\isvalidkey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Cache/CacheKeys.php' => 
    array (
      0 => 'b7c7e89a544b8caf842dac64bca1066a0d6ecfbf',
      1 => 
      array (
        0 => 'app\\cache\\cachekeys',
      ),
      2 => 
      array (
        0 => 'app\\cache\\post',
        1 => 'app\\cache\\postbyuuid',
        2 => 'app\\cache\\postlist',
        3 => 'app\\cache\\pinnedposts',
        4 => 'app\\cache\\postsbycategory',
        5 => 'app\\cache\\userposts',
        6 => 'app\\cache\\posttags',
        7 => 'app\\cache\\postviews',
        8 => 'app\\cache\\postcomments',
        9 => 'app\\cache\\user',
        10 => 'app\\cache\\userbyemail',
        11 => 'app\\cache\\userprofile',
        12 => 'app\\cache\\userpermissions',
        13 => 'app\\cache\\usersessions',
        14 => 'app\\cache\\systemconfig',
        15 => 'app\\cache\\sitesettings',
        16 => 'app\\cache\\menuitems',
        17 => 'app\\cache\\alltags',
        18 => 'app\\cache\\populartags',
        19 => 'app\\cache\\tagposts',
        20 => 'app\\cache\\sitestats',
        21 => 'app\\cache\\dailystats',
        22 => 'app\\cache\\monthlystats',
        23 => 'app\\cache\\searchresults',
        24 => 'app\\cache\\popularsearches',
        25 => 'app\\cache\\attachment',
        26 => 'app\\cache\\postattachments',
        27 => 'app\\cache\\ratelimitbyip',
        28 => 'app\\cache\\ratelimitbyuser',
        29 => 'app\\cache\\postlock',
        30 => 'app\\cache\\userlock',
        31 => 'app\\cache\\usernotifications',
        32 => 'app\\cache\\unreadnotificationcount',
        33 => 'app\\cache\\buildkey',
        34 => 'app\\cache\\getprefix',
        35 => 'app\\cache\\getseparator',
        36 => 'app\\cache\\isvalidkey',
        37 => 'app\\cache\\parsekey',
        38 => 'app\\cache\\pattern',
        39 => 'app\\cache\\userpattern',
        40 => 'app\\cache\\postpattern',
        41 => 'app\\cache\\postslistpattern',
        42 => 'app\\cache\\statspattern',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/Contracts/PostRepositoryInterface.php' => 
    array (
      0 => '3f4b1bc7b235111646501e526fa49c6adb8a497b',
      1 => 
      array (
        0 => 'app\\repositories\\contracts\\postrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\contracts\\findbyseqnumber',
        1 => 'app\\repositories\\contracts\\findwithlock',
        2 => 'app\\repositories\\contracts\\safedelete',
        3 => 'app\\repositories\\contracts\\safesetpinned',
        4 => 'app\\repositories\\contracts\\getpinnedposts',
        5 => 'app\\repositories\\contracts\\getpostsbytag',
        6 => 'app\\repositories\\contracts\\incrementviews',
        7 => 'app\\repositories\\contracts\\setpinned',
        8 => 'app\\repositories\\contracts\\settags',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/Contracts/RepositoryInterface.php' => 
    array (
      0 => '8167602e4c6eebe5e34391b5ffd49d0fa938f727',
      1 => 
      array (
        0 => 'app\\repositories\\contracts\\repositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\contracts\\find',
        1 => 'app\\repositories\\contracts\\findbyuuid',
        2 => 'app\\repositories\\contracts\\create',
        3 => 'app\\repositories\\contracts\\update',
        4 => 'app\\repositories\\contracts\\delete',
        5 => 'app\\repositories\\contracts\\paginate',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/Contracts/IpRepositoryInterface.php' => 
    array (
      0 => 'fec1c65d940f725797f4400778695155163ac91d',
      1 => 
      array (
        0 => 'app\\repositories\\contracts\\iprepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\contracts\\findbyipaddress',
        1 => 'app\\repositories\\contracts\\getbytype',
        2 => 'app\\repositories\\contracts\\isblacklisted',
        3 => 'app\\repositories\\contracts\\iswhitelisted',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/AttachmentRepository.php' => 
    array (
      0 => 'e80d8dc92de417d19cd5f3e0bc1b76f435e84b17',
      1 => 
      array (
        0 => 'app\\repositories\\attachmentrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\create',
        2 => 'app\\repositories\\find',
        3 => 'app\\repositories\\findbyuuid',
        4 => 'app\\repositories\\getbypostid',
        5 => 'app\\repositories\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/PostRepository.php' => 
    array (
      0 => '72a59c52d53d36efc33ba222e2eca03c1ecea336',
      1 => 
      array (
        0 => 'app\\repositories\\postrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\executeintransaction',
        2 => 'app\\repositories\\invalidatecache',
        3 => 'app\\repositories\\adddeletedatcondition',
        4 => 'app\\repositories\\buildselectquery',
        5 => 'app\\repositories\\preparepostdata',
        6 => 'app\\repositories\\preparenewpostdata',
        7 => 'app\\repositories\\find',
        8 => 'app\\repositories\\findwithlock',
        9 => 'app\\repositories\\findbyuuid',
        10 => 'app\\repositories\\findbyseqnumber',
        11 => 'app\\repositories\\safedelete',
        12 => 'app\\repositories\\safesetpinned',
        13 => 'app\\repositories\\tagsexist',
        14 => 'app\\repositories\\create',
        15 => 'app\\repositories\\assigntags',
        16 => 'app\\repositories\\update',
        17 => 'app\\repositories\\delete',
        18 => 'app\\repositories\\paginate',
        19 => 'app\\repositories\\getpinnedposts',
        20 => 'app\\repositories\\getpostsbytag',
        21 => 'app\\repositories\\incrementviews',
        22 => 'app\\repositories\\setpinned',
        23 => 'app\\repositories\\settags',
        24 => 'app\\repositories\\searchbytitle',
        25 => 'app\\repositories\\findbyuserid',
        26 => 'app\\repositories\\search',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/IpRepository.php' => 
    array (
      0 => '0658d831d4361370abe04b9227e154214ac77635',
      1 => 
      array (
        0 => 'app\\repositories\\iprepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\getcachekey',
        2 => 'app\\repositories\\validateipaddress',
        3 => 'app\\repositories\\ipinrange',
        4 => 'app\\repositories\\createiplistfromdata',
        5 => 'app\\repositories\\create',
        6 => 'app\\repositories\\find',
        7 => 'app\\repositories\\findbyuuid',
        8 => 'app\\repositories\\findbyipaddress',
        9 => 'app\\repositories\\update',
        10 => 'app\\repositories\\delete',
        11 => 'app\\repositories\\getbytype',
        12 => 'app\\repositories\\paginate',
        13 => 'app\\repositories\\isblacklisted',
        14 => 'app\\repositories\\iswhitelisted',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Repositories/UserRepository.php' => 
    array (
      0 => '71c1b013e4b74fbe7cd4c153238bdadb6403a74c',
      1 => 
      array (
        0 => 'app\\repositories\\userrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\create',
        2 => 'app\\repositories\\update',
        3 => 'app\\repositories\\delete',
        4 => 'app\\repositories\\findbyid',
        5 => 'app\\repositories\\findbyuuid',
        6 => 'app\\repositories\\findbyusername',
        7 => 'app\\repositories\\findbyemail',
        8 => 'app\\repositories\\updatelastlogin',
        9 => 'app\\repositories\\updatepassword',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Models/Post.php' => 
    array (
      0 => 'be6f74abb2cdab6fc2557a2b55c963078abcded9',
      1 => 
      array (
        0 => 'app\\models\\post',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\getid',
        2 => 'app\\models\\getuuid',
        3 => 'app\\models\\getseqnumber',
        4 => 'app\\models\\gettitle',
        5 => 'app\\models\\getcontent',
        6 => 'app\\models\\getuserid',
        7 => 'app\\models\\getuserip',
        8 => 'app\\models\\ispinned',
        9 => 'app\\models\\getispinned',
        10 => 'app\\models\\getstatus',
        11 => 'app\\models\\getpublishdate',
        12 => 'app\\models\\getviews',
        13 => 'app\\models\\getviewcount',
        14 => 'app\\models\\getcreatedat',
        15 => 'app\\models\\getupdatedat',
        16 => 'app\\models\\toarray',
        17 => 'app\\models\\tosafearray',
        18 => 'app\\models\\jsonserialize',
        19 => 'app\\models\\fromarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Models/Role.php' => 
    array (
      0 => '13e156bca911d1fb54e0a1425a6295dda3587423',
      1 => 
      array (
        0 => 'app\\models\\role',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\getid',
        2 => 'app\\models\\getname',
        3 => 'app\\models\\getdescription',
        4 => 'app\\models\\getcreatedat',
        5 => 'app\\models\\getupdatedat',
        6 => 'app\\models\\toarray',
        7 => 'app\\models\\fromarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Models/IpList.php' => 
    array (
      0 => '36dd1e0e4f72c5a2aed15e4fb6211c291b60e69c',
      1 => 
      array (
        0 => 'app\\models\\iplist',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\fromarray',
        2 => 'app\\models\\getid',
        3 => 'app\\models\\getuuid',
        4 => 'app\\models\\getipaddress',
        5 => 'app\\models\\gettype',
        6 => 'app\\models\\getunitid',
        7 => 'app\\models\\getdescription',
        8 => 'app\\models\\getcreatedat',
        9 => 'app\\models\\getupdatedat',
        10 => 'app\\models\\iswhitelist',
        11 => 'app\\models\\isblacklist',
        12 => 'app\\models\\toarray',
        13 => 'app\\models\\tosafearray',
        14 => 'app\\models\\jsonserialize',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Models/Attachment.php' => 
    array (
      0 => '8c53b191b522ce05889e5c4cedd627d20fad48cb',
      1 => 
      array (
        0 => 'app\\models\\attachment',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\getid',
        2 => 'app\\models\\getuuid',
        3 => 'app\\models\\getpostid',
        4 => 'app\\models\\getfilename',
        5 => 'app\\models\\getoriginalname',
        6 => 'app\\models\\getmimetype',
        7 => 'app\\models\\getfilesize',
        8 => 'app\\models\\getstoragepath',
        9 => 'app\\models\\getcreatedat',
        10 => 'app\\models\\getupdatedat',
        11 => 'app\\models\\getdeletedat',
        12 => 'app\\models\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Models/Permission.php' => 
    array (
      0 => '0feb67dfde9ce96fafd004ffa9ed8a00299b065c',
      1 => 
      array (
        0 => 'app\\models\\permission',
      ),
      2 => 
      array (
        0 => 'app\\models\\__construct',
        1 => 'app\\models\\getid',
        2 => 'app\\models\\getname',
        3 => 'app\\models\\getdescription',
        4 => 'app\\models\\getresource',
        5 => 'app\\models\\getaction',
        6 => 'app\\models\\getcreatedat',
        7 => 'app\\models\\getupdatedat',
        8 => 'app\\models\\toarray',
        9 => 'app\\models\\fromarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Post/PostStatusException.php' => 
    array (
      0 => 'dd49fe75bb724c3c79480b1bfe2c5be11eb4250e',
      1 => 
      array (
        0 => 'app\\exceptions\\post\\poststatusexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\post\\__construct',
        1 => 'app\\exceptions\\post\\invalidstatus',
        2 => 'app\\exceptions\\post\\cannottransition',
        3 => 'app\\exceptions\\post\\cannotpublish',
        4 => 'app\\exceptions\\post\\cannotarchive',
        5 => 'app\\exceptions\\post\\cannotdelete',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Post/PostValidationException.php' => 
    array (
      0 => '20ba2f3acd18bc747a6b22e0c2846a8fbdff9e9d',
      1 => 
      array (
        0 => 'app\\exceptions\\post\\postvalidationexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\post\\__construct',
        1 => 'app\\exceptions\\post\\titlerequired',
        2 => 'app\\exceptions\\post\\titletoolong',
        3 => 'app\\exceptions\\post\\contentrequired',
        4 => 'app\\exceptions\\post\\contenttoolong',
        5 => 'app\\exceptions\\post\\invalidcategory',
        6 => 'app\\exceptions\\post\\invalidstatus',
        7 => 'app\\exceptions\\post\\invalidpublishdate',
        8 => 'app\\exceptions\\post\\multipleerrors',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Post/PostNotFoundException.php' => 
    array (
      0 => '6b96830be2d6853e3331a5479e2650c1ac97df7b',
      1 => 
      array (
        0 => 'app\\exceptions\\post\\postnotfoundexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\post\\__construct',
        1 => 'app\\exceptions\\post\\byid',
        2 => 'app\\exceptions\\post\\byuuid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/NotFoundException.php' => 
    array (
      0 => '08201235aad1b7d1f5e6bc5ec9c504490131268a',
      1 => 
      array (
        0 => 'app\\exceptions\\notfoundexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Auth/ForbiddenException.php' => 
    array (
      0 => '23d175a4f130f82076f0b76517f10767eddc2078',
      1 => 
      array (
        0 => 'app\\exceptions\\auth\\forbiddenexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\auth\\__construct',
        1 => 'app\\exceptions\\auth\\insufficientpermissions',
        2 => 'app\\exceptions\\auth\\notowner',
        3 => 'app\\exceptions\\auth\\adminrequired',
        4 => 'app\\exceptions\\auth\\moderatorrequired',
        5 => 'app\\exceptions\\auth\\csrftokenmismatch',
        6 => 'app\\exceptions\\auth\\ipblocked',
        7 => 'app\\exceptions\\auth\\ratelimitexceeded',
        8 => 'app\\exceptions\\auth\\maintenancemode',
        9 => 'app\\exceptions\\auth\\resourcelocked',
        10 => 'app\\exceptions\\auth\\featuredisabled',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Auth/UnauthorizedException.php' => 
    array (
      0 => '69cbacda925197cbb4ed3b4d5b4466eaa7b76922',
      1 => 
      array (
        0 => 'app\\exceptions\\auth\\unauthorizedexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\auth\\__construct',
        1 => 'app\\exceptions\\auth\\notloggedin',
        2 => 'app\\exceptions\\auth\\invalidcredentials',
        3 => 'app\\exceptions\\auth\\tokenexpired',
        4 => 'app\\exceptions\\auth\\tokeninvalid',
        5 => 'app\\exceptions\\auth\\sessionexpired',
        6 => 'app\\exceptions\\auth\\accountdisabled',
        7 => 'app\\exceptions\\auth\\accountlocked',
        8 => 'app\\exceptions\\auth\\emailnotverified',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/StateTransitionException.php' => 
    array (
      0 => 'ad96ca1df4640205d79bf6ba273ce86de7774322',
      1 => 
      array (
        0 => 'app\\exceptions\\statetransitionexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/CsrfTokenException.php' => 
    array (
      0 => '1451d96ca8289f30792e6c64cd58ac17d5a3ada6',
      1 => 
      array (
        0 => 'app\\exceptions\\csrftokenexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/ValidationException.php' => 
    array (
      0 => '1c1b1587ef426d69a5cb8947fd67aab1dc55153a',
      1 => 
      array (
        0 => 'app\\exceptions\\validationexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\__construct',
        1 => 'app\\exceptions\\geterrors',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Exceptions/Validation/RequestValidationException.php' => 
    array (
      0 => 'a1009d4f43d1abdc8491cc644b6c10c5cb4fd0e9',
      1 => 
      array (
        0 => 'app\\exceptions\\validation\\requestvalidationexception',
      ),
      2 => 
      array (
        0 => 'app\\exceptions\\validation\\__construct',
        1 => 'app\\exceptions\\validation\\invalidjson',
        2 => 'app\\exceptions\\validation\\missingrequiredfields',
        3 => 'app\\exceptions\\validation\\invalidfieldtype',
        4 => 'app\\exceptions\\validation\\fieldtoolong',
        5 => 'app\\exceptions\\validation\\fieldtooshort',
        6 => 'app\\exceptions\\validation\\invalidemail',
        7 => 'app\\exceptions\\validation\\invalidurl',
        8 => 'app\\exceptions\\validation\\invaliddate',
        9 => 'app\\exceptions\\validation\\valuenotinlist',
        10 => 'app\\exceptions\\validation\\numericrangeerror',
        11 => 'app\\exceptions\\validation\\duplicatevalue',
        12 => 'app\\exceptions\\validation\\invalidfiletype',
        13 => 'app\\exceptions\\validation\\filetoolarge',
        14 => 'app\\exceptions\\validation\\customvalidation',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Schemas/PostSchema.php' => 
    array (
      0 => 'f007dfea7bd489caa4fa65425b98dae4ce417219',
      1 => 
      array (
        0 => 'app\\schemas\\postschema',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Schemas/PostRequestSchema.php' => 
    array (
      0 => 'a55004b9d978fda712d71fc6fbf640116a5d5798',
      1 => 
      array (
        0 => 'app\\schemas\\postrequestschema',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Schemas/AuthSchema.php' => 
    array (
      0 => 'c567a30dd47db4ba9014c191df773f09a9effc11',
      1 => 
      array (
        0 => 'app\\schemas\\authschema',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/Post/UpdatePostDTO.php' => 
    array (
      0 => '42efed78e0383cdb3d1e3168aa82d62993fe6c0c',
      1 => 
      array (
        0 => 'app\\dtos\\post\\updatepostdto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\post\\__construct',
        1 => 'app\\dtos\\post\\validate',
        2 => 'app\\dtos\\post\\toarray',
        3 => 'app\\dtos\\post\\haschanges',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/Post/CreatePostDTO.php' => 
    array (
      0 => '0928ee9c33749c32933b95ddfbcc7881313ac607',
      1 => 
      array (
        0 => 'app\\dtos\\post\\createpostdto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\post\\__construct',
        1 => 'app\\dtos\\post\\validate',
        2 => 'app\\dtos\\post\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/Auth/RegisterUserDTO.php' => 
    array (
      0 => '0fd9fb48acbdc2d1f3ef76ef74323ee1c50234bb',
      1 => 
      array (
        0 => 'app\\dtos\\auth\\registeruserdto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\auth\\__construct',
        1 => 'app\\dtos\\auth\\validate',
        2 => 'app\\dtos\\auth\\toarray',
        3 => 'app\\dtos\\auth\\getpassworddata',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/Attachment/CreateAttachmentDTO.php' => 
    array (
      0 => '81b14bc715d87d886e6f3d82ac1b852775b8ffea',
      1 => 
      array (
        0 => 'app\\dtos\\attachment\\createattachmentdto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\attachment\\__construct',
        1 => 'app\\dtos\\attachment\\validate',
        2 => 'app\\dtos\\attachment\\toarray',
        3 => 'app\\dtos\\attachment\\isimage',
        4 => 'app\\dtos\\attachment\\getextension',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/IpManagement/CreateIpRuleDTO.php' => 
    array (
      0 => '40bad9d66a34abe5e73b0bb64a8504f40576e34d',
      1 => 
      array (
        0 => 'app\\dtos\\ipmanagement\\createipruledto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\ipmanagement\\__construct',
        1 => 'app\\dtos\\ipmanagement\\validate',
        2 => 'app\\dtos\\ipmanagement\\isvalidiporcidr',
        3 => 'app\\dtos\\ipmanagement\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/DTOs/BaseDTO.php' => 
    array (
      0 => 'be3bf31b7f67ff5f6d432746022dda5616e2e46f',
      1 => 
      array (
        0 => 'app\\dtos\\basedto',
      ),
      2 => 
      array (
        0 => 'app\\dtos\\toarray',
        1 => 'app\\dtos\\jsonserialize',
        2 => 'app\\dtos\\validaterequired',
        3 => 'app\\dtos\\getstring',
        4 => 'app\\dtos\\getint',
        5 => 'app\\dtos\\getbool',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Http/ApiResponse.php' => 
    array (
      0 => '724671ddd231dd592c7e8f3a20f0b9b556851698',
      1 => 
      array (
        0 => 'app\\http\\apiresponse',
      ),
      2 => 
      array (
        0 => 'app\\http\\success',
        1 => 'app\\http\\error',
        2 => 'app\\http\\paginated',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/IpController.php' => 
    array (
      0 => '16a699ea45a08e5a5ba0f2b3b9e7ccfded84de89',
      1 => 
      array (
        0 => 'app\\controllers\\ipcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\create',
        2 => 'app\\controllers\\getbytype',
        3 => 'app\\controllers\\checkaccess',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/Security/CSPReportController.php' => 
    array (
      0 => 'e19b7662c9599037965c972d20a7f79189692d4d',
      1 => 
      array (
        0 => 'app\\controllers\\security\\cspreportcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\security\\__construct',
        1 => 'app\\controllers\\security\\handlereport',
        2 => 'app\\controllers\\security\\isvalidcspreport',
        3 => 'app\\controllers\\security\\logviolation',
        4 => 'app\\controllers\\security\\getclientip',
        5 => 'app\\controllers\\security\\calculateseverity',
        6 => 'app\\controllers\\security\\checkforalert',
        7 => 'app\\controllers\\security\\getrecentviolations',
        8 => 'app\\controllers\\security\\sendalert',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/AuthController.php' => 
    array (
      0 => '271771c23ad15a5f66b1da3087000ef27201d7f9',
      1 => 
      array (
        0 => 'app\\controllers\\authcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\register',
        2 => 'app\\controllers\\login',
        3 => 'app\\controllers\\logout',
        4 => 'app\\controllers\\me',
        5 => 'app\\controllers\\refresh',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/SwaggerController.php' => 
    array (
      0 => 'd90ba48cc63a494dfd5bde36e124ea179235637c',
      1 => 
      array (
        0 => 'app\\controllers\\swaggercontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\docs',
        1 => 'app\\controllers\\ui',
        2 => 'app\\controllers\\generateswaggeruihtml',
        3 => 'app\\controllers\\info',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/PostController.php' => 
    array (
      0 => 'bb23080c13bac54f2541da54d26e2ed82936872a',
      1 => 
      array (
        0 => 'app\\controllers\\postcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\index',
        2 => 'app\\controllers\\store',
        3 => 'app\\controllers\\show',
        4 => 'app\\controllers\\update',
        5 => 'app\\controllers\\delete',
        6 => 'app\\controllers\\togglepin',
        7 => 'app\\controllers\\getuserip',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/TestController.php' => 
    array (
      0 => '06356456a6c4ae1ff6eab20d4f6e846a3e97beda',
      1 => 
      array (
        0 => 'app\\controllers\\healthcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\check',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/AttachmentController.php' => 
    array (
      0 => 'a693e41e30572f2f3fc8968cc7886ea862f4e45d',
      1 => 
      array (
        0 => 'app\\controllers\\attachmentcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\getcurrentuserid',
        2 => 'app\\controllers\\upload',
        3 => 'app\\controllers\\download',
        4 => 'app\\controllers\\list',
        5 => 'app\\controllers\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/BaseController.php' => 
    array (
      0 => 'e2a6edb802f6e07598f3cd990526301085f5c5d0',
      1 => 
      array (
        0 => 'app\\controllers\\basecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\jsonresponse',
        1 => 'app\\controllers\\successresponse',
        2 => 'app\\controllers\\errorresponse',
        3 => 'app\\controllers\\paginatedresponse',
        4 => 'app\\controllers\\handleexception',
        5 => 'app\\controllers\\gethttpcodefromexception',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Controllers/HealthController.php' => 
    array (
      0 => 'c7bfe6f16ef82a382a92032ae92b016b49a16b7d',
      1 => 
      array (
        0 => 'app\\controllers\\healthcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\check',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Helpers/functions.php' => 
    array (
      0 => 'cf40983fdbdae6ec32c910c63a18e17bc3f6f494',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'generate_uuid',
        1 => 'format_datetime',
        2 => 'normalize_path',
        3 => 'storage_path',
        4 => 'public_path',
        5 => 'is_valid_ip',
        6 => 'sanitize_filename',
        7 => 'get_file_mime_type',
        8 => 'sanitize_post_array',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/PostService.php' => 
    array (
      0 => '658e18efdc8320512ebac9e84439ab04f58d3f71',
      1 => 
      array (
        0 => 'app\\services\\postservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\createpost',
        2 => 'app\\services\\updatepost',
        3 => 'app\\services\\deletepost',
        4 => 'app\\services\\findbyid',
        5 => 'app\\services\\listposts',
        6 => 'app\\services\\getpinnedposts',
        7 => 'app\\services\\setpinned',
        8 => 'app\\services\\settags',
        9 => 'app\\services\\recordview',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/AttachmentService.php' => 
    array (
      0 => '400d1036212bc2e50e538552500183c6388af864',
      1 => 
      array (
        0 => 'app\\services\\attachmentservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\validatefile',
        2 => 'app\\services\\containsmaliciouscontent',
        3 => 'app\\services\\sanitizeimage',
        4 => 'app\\services\\scanforvirus',
        5 => 'app\\services\\securefilevalidation',
        6 => 'app\\services\\canaccesspost',
        7 => 'app\\services\\canaccessattachment',
        8 => 'app\\services\\upload',
        9 => 'app\\services\\download',
        10 => 'app\\services\\delete',
        11 => 'app\\services\\getbypostid',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/RateLimitService.php' => 
    array (
      0 => 'a484d767ca64474adef5a2d4990b575b68e501eb',
      1 => 
      array (
        0 => 'app\\services\\ratelimitservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\checklimit',
        2 => 'app\\services\\isallowed',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/XssProtectionService.php' => 
    array (
      0 => 'd20e5c0034865d7adee6dd71508b702fcd207aed',
      1 => 
      array (
        0 => 'app\\services\\security\\xssprotectionservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\initializepurifiers',
        2 => 'app\\services\\security\\clean',
        3 => 'app\\services\\security\\cleanhtml',
        4 => 'app\\services\\security\\cleanstrict',
        5 => 'app\\services\\security\\cleanarray',
        6 => 'app\\services\\security\\cleanhtmlarray',
        7 => 'app\\services\\security\\cleanforjs',
        8 => 'app\\services\\security\\cleanforurl',
        9 => 'app\\services\\security\\detectxss',
        10 => 'app\\services\\security\\getrisklevel',
        11 => 'app\\services\\security\\getallowedhtmltags',
        12 => 'app\\services\\security\\getcachepath',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/SecurityHeaderService.php' => 
    array (
      0 => 'e2dc131e6ffd1cb4ff54b5ec0916358ff80e45fc',
      1 => 
      array (
        0 => 'app\\services\\security\\securityheaderservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\setsecurityheaders',
        2 => 'app\\services\\security\\generatenonce',
        3 => 'app\\services\\security\\getcurrentnonce',
        4 => 'app\\services\\security\\handlecspreport',
        5 => 'app\\services\\security\\logcspviolation',
        6 => 'app\\services\\security\\sendtomonitoring',
        7 => 'app\\services\\security\\removeserversignature',
        8 => 'app\\services\\security\\buildcsp',
        9 => 'app\\services\\security\\buildpermissionspolicy',
        10 => 'app\\services\\security\\ishttps',
        11 => 'app\\services\\security\\getdefaultconfig',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/ErrorHandlerService.php' => 
    array (
      0 => 'cd8dd421619083bf974782fdd88b49d806f72b68',
      1 => 
      array (
        0 => 'app\\services\\security\\errorhandlerservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\handleexception',
        2 => 'app\\services\\security\\logsecurityevent',
        3 => 'app\\services\\security\\logauthenticationattempt',
        4 => 'app\\services\\security\\logsuspiciousactivity',
        5 => 'app\\services\\security\\sanitizelogdata',
        6 => 'app\\services\\security\\initializelogger',
        7 => 'app\\services\\security\\registererrorhandlers',
        8 => 'app\\services\\security\\globalexceptionhandler',
        9 => 'app\\services\\security\\globalerrorhandler',
        10 => 'app\\services\\security\\shutdownhandler',
        11 => 'app\\services\\security\\logexception',
        12 => 'app\\services\\security\\getpublicerrormessage',
        13 => 'app\\services\\security\\geterrorcode',
        14 => 'app\\services\\security\\issensitivekey',
        15 => 'app\\services\\security\\containssensitivedata',
        16 => 'app\\services\\security\\truncatestring',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/SessionSecurityService.php' => 
    array (
      0 => '5976d4ee77de1919519f1b8dbfb1c894a879b0c2',
      1 => 
      array (
        0 => 'app\\services\\security\\sessionsecurityservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\initializesecuresession',
        1 => 'app\\services\\security\\regeneratesessionid',
        2 => 'app\\services\\security\\destroysession',
        3 => 'app\\services\\security\\issessionvalid',
        4 => 'app\\services\\security\\updateactivity',
        5 => 'app\\services\\security\\setusersession',
        6 => 'app\\services\\security\\validatesessionip',
        7 => 'app\\services\\security\\validatesessionuseragent',
        8 => 'app\\services\\security\\requiresipverification',
        9 => 'app\\services\\security\\markipchangedetected',
        10 => 'app\\services\\security\\confirmipchange',
        11 => 'app\\services\\security\\isipverificationexpired',
        12 => 'app\\services\\security\\performsecuritycheck',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/XssProtectionExtensionService.php' => 
    array (
      0 => '0c9e4eeb4c43ecdfd7209656765b62875eeb11b0',
      1 => 
      array (
        0 => 'app\\services\\security\\xssprotectionextensionservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\protectbycontext',
        2 => 'app\\services\\security\\protectrichtexteditor',
        3 => 'app\\services\\security\\protectuserbio',
        4 => 'app\\services\\security\\protectposttitle',
        5 => 'app\\services\\security\\protectpostcontent',
        6 => 'app\\services\\security\\protectcomment',
        7 => 'app\\services\\security\\protectsearchquery',
        8 => 'app\\services\\security\\protecturlparameter',
        9 => 'app\\services\\security\\protectjsondata',
        10 => 'app\\services\\security\\protectfileupload',
        11 => 'app\\services\\security\\protectgeneric',
        12 => 'app\\services\\security\\cleanjsonrecursively',
        13 => 'app\\services\\security\\cleanfilename',
        14 => 'app\\services\\security\\calculatesecurityscore',
        15 => 'app\\services\\security\\getdefaultconfig',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/CsrfProtectionService.php' => 
    array (
      0 => '5d152a2a443fcaf582f9fcedbee3486a2fb47fce',
      1 => 
      array (
        0 => 'app\\services\\security\\csrfprotectionservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\generatetoken',
        1 => 'app\\services\\security\\validatetoken',
        2 => 'app\\services\\security\\validatetokenfrompool',
        3 => 'app\\services\\security\\validatesingletoken',
        4 => 'app\\services\\security\\cleanexpiredtokens',
        5 => 'app\\services\\security\\limitpoolsize',
        6 => 'app\\services\\security\\istokenvalid',
        7 => 'app\\services\\security\\initializetokenpool',
        8 => 'app\\services\\security\\gettokenpoolstatus',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/FileSecurityService.php' => 
    array (
      0 => '390627902208b818e382e8b6e4c242d17ec41e3b',
      1 => 
      array (
        0 => 'app\\services\\security\\filesecurityservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\validateupload',
        1 => 'app\\services\\security\\generatesecurefilename',
        2 => 'app\\services\\security\\detectactualmimetype',
        3 => 'app\\services\\security\\sanitizefilename',
        4 => 'app\\services\\security\\isinalloweddirectory',
        5 => 'app\\services\\security\\validatebasicproperties',
        6 => 'app\\services\\security\\validatefilename',
        7 => 'app\\services\\security\\validatemimetype',
        8 => 'app\\services\\security\\validatefilecontent',
        9 => 'app\\services\\security\\containsmaliciouscontent',
        10 => 'app\\services\\security\\validatefilesignature',
        11 => 'app\\services\\security\\extractsafeextension',
        12 => 'app\\services\\security\\getuploaderrormessage',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/ContentModerationService.php' => 
    array (
      0 => 'dfc39490ca8bb7cb98c95800e0e6e182bd8b7f44',
      1 => 
      array (
        0 => 'app\\services\\security\\contentmoderationservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\moderatecontent',
        2 => 'app\\services\\security\\checksecurity',
        3 => 'app\\services\\security\\checkquality',
        4 => 'app\\services\\security\\checksensitivewords',
        5 => 'app\\services\\security\\calculatespamscore',
        6 => 'app\\services\\security\\determinefinalstatus',
        7 => 'app\\services\\security\\isrepetitivecontent',
        8 => 'app\\services\\security\\isallcaps',
        9 => 'app\\services\\security\\getuppercaseratio',
        10 => 'app\\services\\security\\hasexcessiverepetition',
        11 => 'app\\services\\security\\hassuspiciousurls',
        12 => 'app\\services\\security\\getsensitivewordseverity',
        13 => 'app\\services\\security\\getdefaultconfig',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/SecretsManager.php' => 
    array (
      0 => 'bc3ff6a1fc4cd6afb04c075a68ad7713d9fdebf8',
      1 => 
      array (
        0 => 'app\\services\\security\\secretsmanager',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\load',
        2 => 'app\\services\\security\\get',
        3 => 'app\\services\\security\\set',
        4 => 'app\\services\\security\\has',
        5 => 'app\\services\\security\\getrequired',
        6 => 'app\\services\\security\\validaterequiredsecrets',
        7 => 'app\\services\\security\\isproduction',
        8 => 'app\\services\\security\\isdevelopment',
        9 => 'app\\services\\security\\getsecretssummary',
        10 => 'app\\services\\security\\generatesecret',
        11 => 'app\\services\\security\\validateenvfile',
        12 => 'app\\services\\security\\loadfromenvironment',
        13 => 'app\\services\\security\\loadfromfile',
        14 => 'app\\services\\security\\parsevalue',
        15 => 'app\\services\\security\\issensitivekey',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/FileSecurityServiceInterface.php' => 
    array (
      0 => '2a4baae39de5f59d9e039c037d4e84199168ef11',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\filesecurityserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\validateupload',
        1 => 'app\\services\\security\\contracts\\generatesecurefilename',
        2 => 'app\\services\\security\\contracts\\detectactualmimetype',
        3 => 'app\\services\\security\\contracts\\sanitizefilename',
        4 => 'app\\services\\security\\contracts\\isinalloweddirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/ErrorHandlerServiceInterface.php' => 
    array (
      0 => '8726335f22a2280d54519fdcf6da830f814c67ba',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\errorhandlerserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\handleexception',
        1 => 'app\\services\\security\\contracts\\logsecurityevent',
        2 => 'app\\services\\security\\contracts\\logauthenticationattempt',
        3 => 'app\\services\\security\\contracts\\logsuspiciousactivity',
        4 => 'app\\services\\security\\contracts\\sanitizelogdata',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/SecurityTestInterface.php' => 
    array (
      0 => 'a4649ce02e1ee1052a676a9ccb50e4c6e93af4b8',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\securitytestinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\runalltests',
        1 => 'app\\services\\security\\contracts\\testsessionsecurity',
        2 => 'app\\services\\security\\contracts\\testauthorization',
        3 => 'app\\services\\security\\contracts\\testfilesecurity',
        4 => 'app\\services\\security\\contracts\\testsecurityheaders',
        5 => 'app\\services\\security\\contracts\\testerrorhandling',
        6 => 'app\\services\\security\\contracts\\testpasswordsecurity',
        7 => 'app\\services\\security\\contracts\\testsecretsmanagement',
        8 => 'app\\services\\security\\contracts\\testsystemsecurity',
        9 => 'app\\services\\security\\contracts\\generatesecurityreport',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/SecurityHeaderServiceInterface.php' => 
    array (
      0 => 'b7e1c3547babc2928143fa3e67825dfe0c73e487',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\securityheaderserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\setsecurityheaders',
        1 => 'app\\services\\security\\contracts\\removeserversignature',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/LoggingSecurityServiceInterface.php' => 
    array (
      0 => 'c6b34c31ea7eea0f5af76e926d7f601522e75482',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\loggingsecurityserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\info',
        1 => 'app\\services\\security\\contracts\\warning',
        2 => 'app\\services\\security\\contracts\\error',
        3 => 'app\\services\\security\\contracts\\logsecurityevent',
        4 => 'app\\services\\security\\contracts\\logcriticalsecurityevent',
        5 => 'app\\services\\security\\contracts\\logrequest',
        6 => 'app\\services\\security\\contracts\\logauthenticationfailure',
        7 => 'app\\services\\security\\contracts\\logauthorizationfailure',
        8 => 'app\\services\\security\\contracts\\verifylogfilepermissions',
        9 => 'app\\services\\security\\contracts\\getlogstatistics',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/XssProtectionServiceInterface.php' => 
    array (
      0 => '7108e0ff95d693b81334fd46d5c4683b804fbfa0',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\xssprotectionserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\sanitize',
        1 => 'app\\services\\security\\contracts\\sanitizearray',
        2 => 'app\\services\\security\\contracts\\cleanarray',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/SessionSecurityServiceInterface.php' => 
    array (
      0 => '72315d22fcbc5a168392d7da3a4e955ffc45d91e',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\sessionsecurityserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\initializesecuresession',
        1 => 'app\\services\\security\\contracts\\regeneratesessionid',
        2 => 'app\\services\\security\\contracts\\destroysession',
        3 => 'app\\services\\security\\contracts\\issessionvalid',
        4 => 'app\\services\\security\\contracts\\updateactivity',
        5 => 'app\\services\\security\\contracts\\setusersession',
        6 => 'app\\services\\security\\contracts\\validatesessionip',
        7 => 'app\\services\\security\\contracts\\validatesessionuseragent',
        8 => 'app\\services\\security\\contracts\\requiresipverification',
        9 => 'app\\services\\security\\contracts\\markipchangedetected',
        10 => 'app\\services\\security\\contracts\\confirmipchange',
        11 => 'app\\services\\security\\contracts\\isipverificationexpired',
        12 => 'app\\services\\security\\contracts\\performsecuritycheck',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/AuthorizationServiceInterface.php' => 
    array (
      0 => 'd16b52a48bb1042de74aec06ccc620fee01072a2',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\authorizationserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\haspermission',
        1 => 'app\\services\\security\\contracts\\hasrole',
        2 => 'app\\services\\security\\contracts\\can',
        3 => 'app\\services\\security\\contracts\\assignrole',
        4 => 'app\\services\\security\\contracts\\removerole',
        5 => 'app\\services\\security\\contracts\\givepermission',
        6 => 'app\\services\\security\\contracts\\revokepermission',
        7 => 'app\\services\\security\\contracts\\getuserroles',
        8 => 'app\\services\\security\\contracts\\getuserpermissions',
        9 => 'app\\services\\security\\contracts\\issuperadmin',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/PasswordSecurityServiceInterface.php' => 
    array (
      0 => '1b819a3548a30e2cb0d7b9781b34a4ed3ae8d022',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\passwordsecurityserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\hashpassword',
        1 => 'app\\services\\security\\contracts\\verifypassword',
        2 => 'app\\services\\security\\contracts\\needsrehash',
        3 => 'app\\services\\security\\contracts\\validatepassword',
        4 => 'app\\services\\security\\contracts\\generatesecurepassword',
        5 => 'app\\services\\security\\contracts\\calculatepasswordstrength',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/SecretsManagerInterface.php' => 
    array (
      0 => '7f6324fdc22eb4637602f53ba22f17c37603c37b',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\secretsmanagerinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\load',
        1 => 'app\\services\\security\\contracts\\get',
        2 => 'app\\services\\security\\contracts\\set',
        3 => 'app\\services\\security\\contracts\\has',
        4 => 'app\\services\\security\\contracts\\getrequired',
        5 => 'app\\services\\security\\contracts\\validaterequiredsecrets',
        6 => 'app\\services\\security\\contracts\\isproduction',
        7 => 'app\\services\\security\\contracts\\isdevelopment',
        8 => 'app\\services\\security\\contracts\\getsecretssummary',
        9 => 'app\\services\\security\\contracts\\generatesecret',
        10 => 'app\\services\\security\\contracts\\validateenvfile',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/Contracts/CsrfProtectionServiceInterface.php' => 
    array (
      0 => '21861c7d0a4f6a8f073b6fa675fb62835d2be09c',
      1 => 
      array (
        0 => 'app\\services\\security\\contracts\\csrfprotectionserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\contracts\\generatetoken',
        1 => 'app\\services\\security\\contracts\\validatetoken',
        2 => 'app\\services\\security\\contracts\\gettokenfromrequest',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/SecurityTestService.php' => 
    array (
      0 => 'c8f8e4a6daa36795ca7c6ef604e331acc9299106',
      1 => 
      array (
        0 => 'app\\services\\security\\securitytestservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\runalltests',
        2 => 'app\\services\\security\\testsessionsecurity',
        3 => 'app\\services\\security\\testauthorization',
        4 => 'app\\services\\security\\testfilesecurity',
        5 => 'app\\services\\security\\testsecurityheaders',
        6 => 'app\\services\\security\\testerrorhandling',
        7 => 'app\\services\\security\\testpasswordsecurity',
        8 => 'app\\services\\security\\testsecretsmanagement',
        9 => 'app\\services\\security\\testsystemsecurity',
        10 => 'app\\services\\security\\generatesecurityreport',
        11 => 'app\\services\\security\\createmockuploadedfile',
        12 => 'app\\services\\security\\getclientfilename',
        13 => 'app\\services\\security\\getclientmediatype',
        14 => 'app\\services\\security\\getsize',
        15 => 'app\\services\\security\\geterror',
        16 => 'app\\services\\security\\getstream',
        17 => 'app\\services\\security\\getsecuritylevel',
        18 => 'app\\services\\security\\getrecommendations',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/AdvancedRateLimitService.php' => 
    array (
      0 => 'b079a4b23f11095bcc3f4f7ed0c2e3491628a54a',
      1 => 
      array (
        0 => 'app\\services\\security\\advancedratelimitservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\checklimit',
        2 => 'app\\services\\security\\getrealclientip',
        3 => 'app\\services\\security\\checkuserlimit',
        4 => 'app\\services\\security\\checkiplimit',
        5 => 'app\\services\\security\\performlimitcheck',
        6 => 'app\\services\\security\\getlimitsforaction',
        7 => 'app\\services\\security\\istrustedproxy',
        8 => 'app\\services\\security\\ipinrange',
        9 => 'app\\services\\security\\clearlimit',
        10 => 'app\\services\\security\\getlimitstatus',
        11 => 'app\\services\\security\\getdefaultconfig',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/RichTextProcessorService.php' => 
    array (
      0 => 'abd0ccc139a4fc5c43482f9460da5b32d2126703',
      1 => 
      array (
        0 => 'app\\services\\security\\richtextprocessorservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\initializepurifiers',
        2 => 'app\\services\\security\\processcontent',
        3 => 'app\\services\\security\\processckeditorcontent',
        4 => 'app\\services\\security\\preprocessckeditorcontent',
        5 => 'app\\services\\security\\getallowedelements',
        6 => 'app\\services\\security\\generatestatistics',
        7 => 'app\\services\\security\\generatepreview',
        8 => 'app\\services\\security\\validatesecurity',
        9 => 'app\\services\\security\\getcachepath',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/PasswordSecurityService.php' => 
    array (
      0 => '37a2366cdc49888e4f74f7c33660b9b260ec2efd',
      1 => 
      array (
        0 => 'app\\services\\security\\passwordsecurityservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\hashpassword',
        2 => 'app\\services\\security\\verifypassword',
        3 => 'app\\services\\security\\needsrehash',
        4 => 'app\\services\\security\\validatepassword',
        5 => 'app\\services\\security\\generatesecurepassword',
        6 => 'app\\services\\security\\calculatepasswordstrength',
        7 => 'app\\services\\security\\validatepasswordcomplexity',
        8 => 'app\\services\\security\\iscommonpassword',
        9 => 'app\\services\\security\\hasexcessiverepetition',
        10 => 'app\\services\\security\\hassequentialchars',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/AuthorizationService.php' => 
    array (
      0 => 'a2f59df3b49314b49f3a22e5d76ba9d434eff7e4',
      1 => 
      array (
        0 => 'app\\services\\security\\authorizationservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\haspermission',
        2 => 'app\\services\\security\\hasrole',
        3 => 'app\\services\\security\\can',
        4 => 'app\\services\\security\\assignrole',
        5 => 'app\\services\\security\\removerole',
        6 => 'app\\services\\security\\givepermission',
        7 => 'app\\services\\security\\revokepermission',
        8 => 'app\\services\\security\\getuserroles',
        9 => 'app\\services\\security\\getuserpermissions',
        10 => 'app\\services\\security\\issuperadmin',
        11 => 'app\\services\\security\\clearusercache',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/PwnedPasswordService.php' => 
    array (
      0 => '48b2f2574d0a0707483f033f0e602cbc892be7c8',
      1 => 
      array (
        0 => 'app\\services\\security\\pwnedpasswordservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\ispasswordpwned',
        2 => 'app\\services\\security\\fetchhashesfromapi',
        3 => 'app\\services\\security\\findhashinlist',
        4 => 'app\\services\\security\\isincache',
        5 => 'app\\services\\security\\getfromcache',
        6 => 'app\\services\\security\\setcache',
        7 => 'app\\services\\security\\clearcache',
        8 => 'app\\services\\security\\getapistatus',
        9 => 'app\\services\\security\\checkmultiplepasswords',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Security/LoggingSecurityService.php' => 
    array (
      0 => 'd444b3277adfc36e394bde7abb0d26de8ba4f1fd',
      1 => 
      array (
        0 => 'app\\services\\security\\loggingsecurityservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\security\\__construct',
        1 => 'app\\services\\security\\initializeloggers',
        2 => 'app\\services\\security\\ensurelogdirectory',
        3 => 'app\\services\\security\\setlogfilepermissions',
        4 => 'app\\services\\security\\info',
        5 => 'app\\services\\security\\warning',
        6 => 'app\\services\\security\\error',
        7 => 'app\\services\\security\\logsecurityevent',
        8 => 'app\\services\\security\\logcriticalsecurityevent',
        9 => 'app\\services\\security\\logrequest',
        10 => 'app\\services\\security\\logauthenticationfailure',
        11 => 'app\\services\\security\\logauthorizationfailure',
        12 => 'app\\services\\security\\applyrequestwhitelist',
        13 => 'app\\services\\security\\sanitizecontext',
        14 => 'app\\services\\security\\recursivesanitize',
        15 => 'app\\services\\security\\enrichsecuritycontext',
        16 => 'app\\services\\security\\enrichrequestcontext',
        17 => 'app\\services\\security\\verifylogfilepermissions',
        18 => 'app\\services\\security\\getlogstatistics',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Contracts/PostServiceInterface.php' => 
    array (
      0 => '253c97ca88efcc05d47361af49abb89ae44183bc',
      1 => 
      array (
        0 => 'app\\services\\contracts\\postserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\contracts\\createpost',
        1 => 'app\\services\\contracts\\updatepost',
        2 => 'app\\services\\contracts\\deletepost',
        3 => 'app\\services\\contracts\\findbyid',
        4 => 'app\\services\\contracts\\listposts',
        5 => 'app\\services\\contracts\\getpinnedposts',
        6 => 'app\\services\\contracts\\setpinned',
        7 => 'app\\services\\contracts\\settags',
        8 => 'app\\services\\contracts\\recordview',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Contracts/AttachmentServiceInterface.php' => 
    array (
      0 => '71a947e975c38a7629edabe7048b5d829c115f51',
      1 => 
      array (
        0 => 'app\\services\\contracts\\attachmentserviceinterface',
      ),
      2 => 
      array (
        0 => 'app\\services\\contracts\\upload',
        1 => 'app\\services\\contracts\\download',
        2 => 'app\\services\\contracts\\delete',
        3 => 'app\\services\\contracts\\validatefile',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/AuthService.php' => 
    array (
      0 => '71f4851fe8fb9f960bfe96efacb56b7a0696a664',
      1 => 
      array (
        0 => 'app\\services\\authservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\register',
        2 => 'app\\services\\login',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/PasswordManagementService.php' => 
    array (
      0 => '4cb70be95c924d8aca2bf3bc25d70d3bd665f638',
      1 => 
      array (
        0 => 'app\\services\\passwordmanagementservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\changepassword',
        2 => 'app\\services\\resetpassword',
        3 => 'app\\services\\checkpasswordstrength',
        4 => 'app\\services\\generatesecurepassword',
        5 => 'app\\services\\needsrehash',
        6 => 'app\\services\\upgradepasswordhash',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Enums/PostStatus.php' => 
    array (
      0 => '70e6c8ee458b291ef5cb52e3a50079b22a6ce64e',
      1 => 
      array (
        0 => 'app\\services\\enums\\poststatus',
      ),
      2 => 
      array (
        0 => 'app\\services\\enums\\getlabel',
        1 => 'app\\services\\enums\\cantransitionto',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Enums/FileRules.php' => 
    array (
      0 => '70e104b2f08b890c374a6b1a69bdacfd6b1e6f0e',
      1 => 
      array (
        0 => 'app\\services\\enums\\filerules',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/CacheService.php' => 
    array (
      0 => '7396382ace2c90b6e0a4340fa3991a24f9f33cd3',
      1 => 
      array (
        0 => 'app\\services\\cacheservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\get',
        2 => 'app\\services\\set',
        3 => 'app\\services\\delete',
        4 => 'app\\services\\deletepattern',
        5 => 'app\\services\\clear',
        6 => 'app\\services\\remember',
        7 => 'app\\services\\getcachefilename',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/OutputSanitizer.php' => 
    array (
      0 => '681c5ff5c42b754f0c6193ac548983f83e9ade01',
      1 => 
      array (
        0 => 'app\\services\\outputsanitizer',
      ),
      2 => 
      array (
        0 => 'app\\services\\sanitizehtml',
        1 => 'app\\services\\sanitizetitle',
        2 => 'app\\services\\sanitizefordisplay',
        3 => 'app\\services\\sanitizepreservenewlines',
        4 => 'app\\services\\sanitizeandtruncate',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/IpService.php' => 
    array (
      0 => 'b1c75f51a598dd955695194481eb3a3a9ae35cf8',
      1 => 
      array (
        0 => 'app\\services\\ipservice',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\createiprule',
        2 => 'app\\services\\isipallowed',
        3 => 'app\\services\\getrulesbytype',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/src/Services/Validators/PostValidator.php' => 
    array (
      0 => '78d92fecc76dc8e1f35aa7d778ada67aca981033',
      1 => 
      array (
        0 => 'app\\services\\validators\\postvalidator',
      ),
      2 => 
      array (
        0 => 'app\\services\\validators\\__construct',
        1 => 'app\\services\\validators\\validateforcreation',
        2 => 'app\\services\\validators\\validateforupdate',
        3 => 'app\\services\\validators\\validatetagassignment',
        4 => 'app\\services\\validators\\validaterequiredfields',
        5 => 'app\\services\\validators\\validatefieldlengths',
        6 => 'app\\services\\validators\\validatedatatypes',
        7 => 'app\\services\\validators\\validatestatus',
        8 => 'app\\services\\validators\\validatepublishdate',
        9 => 'app\\services\\validators\\validateuserip',
        10 => 'app\\services\\validators\\tagsexist',
      ),
      3 => 
      array (
      ),
    ),
  ),
));