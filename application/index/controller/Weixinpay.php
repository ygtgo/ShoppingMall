<?php
namespace app\index\controller;
use think\Controller;

class Weixinpay extends controller
{
    public function index()
    {
        //流式数据
        public function notify(){
        	//测试
        	$wenxinData = file_get_contents("php://input");  //获取

        	//存入
        	file_put_contents('/tmp/2.txt', $weixinData,FILE_APPEND); //在文件末尾以追加的方式写入数据

        	try {
        		$resultObj = new WxPayResults();
        		$weixinData = $resultObj->Init($weixinData); //xml转数组并且校验
        	} catch (\Exception $e) {
        		$resultObj->setData('return_code','FAIL');
        		$resultObj->setData('return_msg','error');
        		return $resultObj->toXml();  //商户返回接收状态给微信
        	}
        	if($weixinData['return_code'] === 'FAIL' || $weixinData['result_code'] !== 'SUCCESS'){
        		$resultObj->setData('return_code','FAIL');
        		$resultObj->setData('return_msg','error');
        		return $resultObj->toXml();
        	}

        	//根据out_trade_to 来查询订单数据
        	$outTradeTo = $wenxinData['out_trade_no'];
        	$order = model('Order')->get(['out_trade_no' => $outTradeTo]);
        	if(!$order || $order->pay_status == 1){
        		$resultObj->setData('return_code','SUCCESS');
        		$resultObj->setData('return_msg','OK');
        		return $resultObj->toXml();
        	}

        	//更新表 订单表 商品表
        	
        	try {
        		$orderRes = model('Order')->updateOrderByOutTradeNo($outTradeTo,$weixinData);
        		model('Deal')->updateBuyCountId($order->deal_id,$order->deal_count)


        		//消费券生成
        		$coupons = [
        			'sn' => $outTradeTo,
        			'password' => rand(10000,99999),
        			'user_id' => $order->user_id,
        			'deal_id' =>$order->deal_id,
        			'order_id' => $order->id,
        		];
        		model('Coupons')->add($coupons);

        	} catch (\Exception $e) {
        		return false;
        	}
        	$resultObj->setData('return_code','SUCCESS');
        		$resultObj->setData('return_msg','OK');
        		return $resultObj->toXml();
        }
    }
}