# lock
## 概述
* Lock包使用Redis来实现并发锁功能
* 支持设置超时时间
* 支持锁过期时间

## 使用
1、安装扩展

* 支持composer安装

		{
		  "require": {
		    "php": ">=5.3.0",
		    "fwhappy/lock": "^1.0"
		  },
		  "repositories": {
		    "packagist": {
		      "type": "composer",
		      "url": "https://packagist.org"
		    }
		  }
		}

* 也可以直接clone，放到项目中

		git clone git@github.com:fwhappy/lock.git


2、使用示例
	
	require __DIR__ . '/vendor/autoload.php';

	use Camry\Lock\Lock as Lock;
	
	$redis = new Redis();
	$redis->connect("127.0.0.1", 7480);
	$redis->select(7);
	
	$lock = new Lock($redis);
	echo str_pad(" ", 4096);
	
	if ($lock->acquire("TEST:LOCK:KEY", 10)) {
	    echo "操作成功" . time();
	    sleep(5);
	    $lock->release("TEST:LOCK:KEY");
	} else {
	    echo "操作失败" . time();
	}
