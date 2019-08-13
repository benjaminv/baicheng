//本js文件可以作为基础子级插件，document元素绑定事件v-on:click='cityPatFun'即可调用，选择城市后将data传入父级组件
Vue.component('com-citychoice',{
	data:function(){
		return {
			isCancel:false,
			zimShow:false,
			ssval:'',
			zimText:'',
			sidabers:[
				'热门','A','B','C','D','E','F','G','H','J','K','L','M','N','P','Q','R','S','T','W','X','Y','Z'
			],
			citys:[
				 {city:'北京市',py:'beijingshi'},{city:'上海市',py:'shanghaishi'},{city:'广州市',py:'guangzhoushi'},{city:'深圳市',py:'shenzhenshi'},{city:'苏州市',py:'suzhoushi'},{city:'无锡市',py:'wuxishi'},{city:'常州市',py:'changzhoushi'},{city:'南京市',py:'nanjingshi'},{city:'合肥市',py:'hefeishi'},{city:'郑州市',py:'zhengzhoushi'},{city:'天津市',py:'tianjinshi'},{city:'重庆市',py:'chongqingshi'},
				 {city:'鞍山市',py:'anshanshi'},{city:'安庆市',py:'anqingshi'},{city:'安阳市',py:'anyangshi'},{city:'阿拉善盟',py:'alashanmeng'},{city:'阿坝州',py:'abeizhou'},{city:'安顺市',py:'anshunshi'},{city:'阿里地区',py:'alidiqu'},{city:'安康市',py:'ankangshi'},{city:'阿克苏地区',py:'akesudiqu'},{city:'阿勒泰地区',py:'aletaidiqu'},{city:'阿拉尔市',py:'alaershi'},
				 {city:'保定市',py:'baodingshi'},{city:'包头市',py:'baotoushi'},{city:'本溪市',py:'benxishi'},{city:'蚌埠市',py:'bengbushi'},{city:'北海市',py:'beihaishi'},{city:'滨州市',py:'binzhoushi'},{city:'宝鸡市',py:'baojishi'},{city:'亳州市',py:'bozhoushi'},{city:'巴彦淖尔市',py:'bayanzhuoershi'},{city:'白山市',py:'baishanshi'},{city:'白城市',py:'baichengshi'},{city:'百色市',py:'baiseshi'},{city:'白沙黎族自治县',py:'baishalizuzizhiqu'},{city:'巴中市',py:'baiseshi'},{city:'毕节地区',py:'bijiediqu'},{city:'保山市',py:'baishanshi'},{city:'白银市',py:'baiyinshi'},{city:'巴音郭楞州',py:'bayinguoleng'},{city:'博尔塔拉州',py:'boertalazhou'},
				 {city:'成都市',py:'chengdushi'},{city:'长沙市',py:'changshashi'},{city:'长春市',py:'changchunshi'},{city:'沧州市',py:'cangzhoushi'},{city:'承德市',py:'chengdeshi'},{city:'常德市',py:'changdeshi'},{city:'郴州市',py:'cengzhoushi'},{city:'长治市',py:'changzhishi'},{city:'滁州市',py:'chuzhoushi'},{city:'池州市',py:'chizhoushi'},{city:'赤峰市',py:'chifengshi'},{city:'巢湖市',py:'chaohushi'},{city:'朝阳市',py:'chaoyangshi'},{city:'潮州市',py:'chaozhoushi'},{city:'崇左市',py:'congzuoshi'},{city:'澄迈县',py:'chengmaixian'},{city:'楚雄州',py:'chuxiongzhou'},{city:'昌都地区',py:'cangdudiqu'},{city:'昌吉州',py:'cangjizhou'},
				 {city:'大连市',py:'dalianshi'},{city:'东莞市',py:'dongguanshi'},{city:'大庆市',py:'daqingshi'},{city:'东营市',py:'dongyingshi'},{city:'德州市',py:'dezhoushi'},{city:'大同市',py:'datongshi'},{city:'大理州',py:'dalizhou'},{city:'丹东市',py:'dandongshi'},{city:'德阳市',py:'deyangshi'},{city:'大兴安岭地区',py:'daxinganling'},{city:'儋州市',py:'danzhoushi'},{city:'东方市',py:'dongfangshi'},{city:'定安县',py:'dinganxian'},{city:'达州市',py:'dazhoushi'},{city:'德宏州',py:'dehongshi'},{city:'迪庆州',py:'diqingzhou'},{city:'定西市',py:'dingxishi'},
				 {city:'鄂尔多斯市',py:'eerduosi'},{city:'鄂州市',py:'ezhoushi'},{city:'恩施州',py:'enchizhou'}, 
				 {city:'福州市',py:'fuzhoushi'},{city:'佛山市',py:'foshanshi'},{city:'抚顺市',py:'fushunshi'},{city:'阜新市',py:'fuxinshi'},{city:'阜阳市',py:'fuyangshi'},{city:'抚州市',py:'fuzhoushi'},{city:'防城港市',py:'fangchenggangshi'},
				 {city:'贵阳市',py:'guiyangshi'},{city:'赣州市',py:'ganzhoushi'},{city:'桂林市',py:'guilinshi'},{city:'贵港市',py:'guigangshi'},{city:'广元市',py:'guangyuanshi'},{city:'广安市',py:'guanganshi'},{city:'甘孜州',py:'ganzizhou'},{city:'甘南州',py:'gannanzhou'},{city:'固原市',py:'guyuanshi'},{city:'果洛州',py:'guoluozhou'},
				 {city:'杭州市',py:'hangzhoushi'},{city:'哈尔滨市',py:'haerbinshi'}, {city:'呼和浩特市',py:'huhehaoteshi'}, {city:'邯郸市',py:'handanshi'}, {city:'葫芦岛市',py:'huludaoshi'}, {city:'衡水市',py:'hengshuishi'},{city:'海口市',py:'haikoushi'},{city:'湖州市',py:'huzhoushi'},{city:'淮安市',py:'huaianshi'},{city:'汉中市',py:'hanzhongshi'},{city:'衡阳市',py:'hengyangshi'},{city:'菏泽市',py:'hezeshi'},{city:'惠州市',py:'huizhoushi'},{city:'黄山市',py:'huangshanshi'},{city:'淮南市',py:'huainanshi'},{city:'淮北市',py:'huaibeishi'},{city:'呼伦贝尔市',py:'hulunbeier'},{city:'鹤岗市',py:'hegangshi'},{city:'黑河市',py:'heiheshi'},{city:'黄石市',py:'huangshishi'},{city:'黄冈市',py:'huanggangshi'},{city:'怀化市',py:'huaihuashi'},{city:'鹤壁市',py:'hebishi'},{city:'河源市',py:'heyuanshi'},{city:'贺州市',py:'hezhoushi'},{city:'河池市',py:'hechishi'},{city:'红河州',py:'hongheshi'},{city:'海东地区',py:'haidongdiqu'},{city:'海北州',py:'haibeizhou'},{city:'黄南州',py:'huangnanzhou'},{city:'海南州',py:'hainanzhou'},{city:'海西州',py:'haixizhou'},{city:'哈密地区',py:'hamidiqu'},{city:'和田地区',py:'hetiandiqu'},
				 {city:'济南市',py:'jinanshi'},{city:'锦州市',py:'jinzhoushi'},{city:'晋中市',py:'jinzhongshi'},{city:'吉林市',py:'jilinshi'},{city:'济宁市',py:'jiningshi'},{city:'金华市',py:'jinhuashi'},{city:'嘉兴市',py:'jiaxingshi'},{city:'九江市',py:'jiujiangshi'},{city:'荆州市',py:'jinzhoushi'},{city:'景德镇市',py:'jingdezhengshi'},{city:'江门市',py:'jiangmenshi'},{city:'揭阳市',py:'jieyangshi'},{city:'焦作市',py:'jiaozuoshi'},{city:'晋城市',py:'jinchengshi'},{city:'鸡西市',py:'jixishi'},{city:'佳木斯市',py:'jiamusishi'},{city:'吉安市',py:'jianshi'},{city:'荆门市',py:'jinmenshi'},{city:'济源市',py:'jiyuanshi'},{city:'金昌市',py:'jinchangshi'},{city:'嘉峪关市',py:'jiayuguanshi'},{city:'酒泉市',py:'jiuquanshi'},
				 {city:'昆明市',py:'kunmingshi'},{city:'开封市',py:'kaifengshi'},{city:'喀什地区',py:'kashidiqu'},{city:'克拉玛依市',py:'kelamayishi'},{city:'克孜勒苏柯州',py:'kezilesukezhou'},
				 {city:'洛阳市',py:'luoyangshi'},{city:'兰州市',py:'lanzhoushi'},{city:'廊坊市',py:'langfangshi'},{city:'临沂市',py:'linyishi'},{city:'辽阳市',py:'liaoyangshi'},{city:'连云港市',py:'lianyungangshi'},{city:'泸州市',py:'luzhoushi'},{city:'莱芜市',py:'laiwushi'},{city:'聊城市',py:'liaochengshi'},{city:'柳州市',py:'liuzhoushi'},{city:'丽江市',py:'lijiangshi'},{city:'丽水市',py:'lishuishi'},{city:'拉萨市',py:'lasashi'},{city:'六安市',py:'liuanshi'},{city:'临汾市',py:'linfenshi'},{city:'吕梁市',py:'lvliangshi'},{city:'辽源市',py:'liaoyuanshi'},{city:'龙岩市',py:'longyanshi'},{city:'娄底市',py:'loudishi'},{city:'漯河市',py:'luoheshi'},{city:'来宾市',py:'laibinshi'},{city:'临高县',py:'lingaoxian'},{city:'乐山市',py:'leshanshi'},{city:'凉山州',py:'liangshanshi'},{city:'六盘水市',py:'liupanshuishi'},{city:'临沧市',py:'lincangshi'},{city:'林芝地区',py:'linzhidiqu'},{city:'陇南市',py:'longnanshi'},{city:'临夏州',py:'linxiazhou'},
				 {city:'绵阳市',py:'mianyangshi'},{city:'马鞍山市',py:'maanshanshi'},{city:'牡丹江市',py:'mudanjiangshi'},{city:'茂名市',py:'maomingshi'},{city:'梅州市',py:'meizhoushi'},{city:'眉山市',py:'meishanshi'},
				 {city:'宁波市',py:'ningboshi'},{city:'南宁市',py:'nanningshi'},{city:'南昌市',py:'nanchangshi'},{city:'南充市',py:'nanchongshi'},{city:'南通市',py:'nantongshi'},{city:'南阳市',py:'nanyangshi'},{city:'宁德市',py:'ningdeshi'},{city:'南平市',py:'nanpingshi'},{city:'内江市',py:'neijiangshi'},{city:'怒江州',py:'nujiangshi'},{city:'那曲地区',py:'naqudiqu'},
				 {city:'平顶山市',py:'pingdingshanshi'},{city:'攀枝花市',py:'panzhihuashi'},{city:'莆田市',py:'putianshi'},{city:'盘锦市',py:'panjinshi'},{city:'濮阳市',py:'puyangshi'},{city:'萍乡市',py:'pinxiangshi'},{city:'普洱市',py:'puershi'},{city:'平凉市',py:'pingliangshi'},
				 {city:'青岛市',py:'qingdaoshi'},{city:'泉州市',py:'quanzhoushi'},{city:'秦皇岛市',py:'qinshihuangdao'},{city:'齐齐哈尔市',py:'qiqihaershi'},{city:'曲靖市',py:'qujingshi'},{city:'衢州市',py:'quzhoushi'},{city:'清远市',py:'qingyuanshi'},{city:'七台河市',py:'qitaiheshi'},{city:'潜江市',py:'qianjiangshi'},{city:'钦州市',py:'qinzhoushi'},{city:'琼海市',py:'qionghaishi'},{city:'黔西南州',py:'qianxinanzhou'},{city:'黔东南州',py:'qiandongnanzhou'},{city:'黔南州',py:'qiannanzhou'},{city:'庆阳市',py:'qingyangshi'},
				 {city:'日照市',py:'rizhaoshi'},{city:'日喀则地区',py:'rikazediqu'},
				 {city:'深圳市',py:'shenzhenshi'},{city:'沈阳市',py:'shenyangshi'},{city:'石家庄市',py:'shijiazhuangshi'},{city:'三亚市',py:'sanyashi'},{city:'绍兴市',py:'shaoxingshi'},{city:'绥化市',py:'suihuashi'},{city:'四平市',py:'sipingshi'},{city:'宿迁市',py:'suqianshi'},{city:'朔州市',py:'suozhoushi'},{city:'松原市',py:'songyuanshi'},{city:'石河子市',py:'shihezishi'},{city:'宿州市',py:'suzhoushi'},{city:'双鸭山市',py:'shuangyashi'},{city:'上饶市',py:'shangraoshi'},{city:'三明市',py:'sanmingshi'},{city:'十堰市',py:'shiyanshi'},{city:'随州市',py:'suizhoushi'},{city:'神农架林区',py:'shennongjialinqu'},{city:'邵阳市',py:'shaoyangshi'},{city:'三门峡市',py:'sanmenxiashi'},{city:'韶关市',py:'shaoguanshi'},{city:'汕尾市',py:'shanweishi'},{city:'遂宁市',py:'suiningshi'},{city:'山南地区',py:'shannandiqu'},{city:'商洛市',py:'shangluoshi'},{city:'石嘴山市',py:'shizuishanshi'},
				 {city:'太原市',py:'taiyuanshi'},{city:'唐山市',py:'tangshanshi'},{city:'铁岭市',py:'tielingshi'},{city:'台州市',py:'taizhoushi'},{city:'泰州市',py:'taizhoushi'},{city:'泰安市',py:'taianshi'},{city:'天水市',py:'tianshuishi'},{city:'铜陵市',py:'tonglingshi'},{city:'通辽市',py:'tongliaoshi'},{city:'通化市',py:'tonghuashi'},{city:'天门市',py:'tianmenshi'},{city:'屯昌县',py:'tunchangxian'},{city:'铜仁地区',py:'tongrendiqu'},{city:'铜川市',py:'tongchuanshi'},{city:'吐鲁番地区',py:'tulufandiqu'},{city:'塔城地区',py:'tachengdiqu'},
				 {city:'武汉市',py:'wuhanshi'},{city:'威海市',py:'weihaishi'},{city:'乌鲁木齐市',py:'wulumuqishi'},{city:'潍坊市',py:'weifangshi'},{city:'温州市',py:'wenzhoushi'},{city:'芜湖市',py:'wuhushi'},{city:'乌海市',py:'wuhaishi'},{city:'乌兰察布市',py:'wulancabushi'},{city:'梧州市',py:'wuzhoushi'},{city:'文昌市',py:'wenchangshi'},{city:'万宁市',py:'wanningshi'},{city:'文山州',py:'wenshanshi'},{city:'渭南市',py:'weinanshi'},{city:'武威市',py:'wuweishi'},{city:'吴忠市',py:'wuzhongshi'},
				 {city:'西安市',py:'xianshi'},{city:'西宁市',py:'xiningshi'},{city:'厦门市',py:'xiamenshi'},{city:'徐州市',py:'xuzhoushi'},{city:'湘潭市',py:'xiangtanshi'},{city:'邢台市',py:'xingtaishi'},{city:'襄阳市',py:'xiangyangshi'},{city:'新乡市',py:'xinxiangshi'},{city:'许昌市',py:'xuchangshi'},{city:'咸阳市',py:'xianyangshi'},{city:'新余市',py:'xinyushi'},{city:'锡林郭勒盟',py:'xilinguolemeng'},{city:'兴安盟',py:'xinganmeng'},{city:'孝感市',py:'xiaoganshi'},{city:'咸宁市',py:'xianningshi'},{city:'仙桃市',py:'xiantaoshi'},{city:'湘西州',py:'xiangxizhou'},{city:'信阳市',py:'xinyangshi'},{city:'西双版纳州',py:'xishuangbannazhou'},
				 {city:'烟台市',py:'yantaishi'},{city:'银川市',py:'yinchuanshi'},{city:'宜昌市',py:'yichangshi'},{city:'岳阳市',py:'yueyangshi'},{city:'营口市',py:'yingkoushi'},{city:'扬州市',py:'yangzhoushi'},{city:'盐城市',py:'yanchengshi'},{city:'运城市',py:'yunchengshi'},{city:'宜宾市',py:'yibinshi'},{city:'阳泉市',py:'yangquanshi'},{city:'延吉市',py:'yanjishi'},{city:'玉林市',py:'yulinshi'},{city:'延安市',py:'yananshi'},{city:'榆林市',py:'yulinshi'},{city:'伊春市',py:'yichunshi'},{city:'鹰潭市',py:'yingtanshi'},{city:'宜春市',py:'yichunshi'},{city:'益阳市',py:'yiyangshi'},{city:'永州市',py:'yongzhoushi'},{city:'阳江市',py:'yangjiangshi'},{city:'云浮市',py:'yunfushi'},{city:'雅安市',py:'yaanshi'},{city:'玉溪市',py:'yuxishi'},{city:'玉树州',py:'yushushi'},{city:'伊犁州',py:'yilizhou'},{city:'宣城市',py:'yichengshi'},{city:'忻州市',py:'yizhoushi'},
				 {city:'遵义市',py:'zunyishi'},{city:'株洲市',py:'zhuzhoushi'},{city:'淄博市',py:'ziboshi'},{city:'张家口市',py:'zhangjiakoushi'},{city:'珠海市',py:'zhuhaishi'},{city:'镇江市',py:'zhenjiangshi'},{city:'周口市',py:'zhoukoushi'},{city:'中山市',py:'zhongshanshi'},{city:'漳州市',py:'zhangzhoushi'},{city:'舟山市',py:'zhoushanshi'},{city:'湛江市',py:'zhenjiangshi'},{city:'肇庆市',py:'zhaoqingshi'},{city:'枣庄市',py:'zaozhuangshi'},{city:'张家界市',py:'zhangjiajieshi'},{city:'驻马店市',py:'zhumadianshi'},{city:'自贡市',py:'zigongshi'},{city:'资阳市',py:'ziyangshi'},{city:'昭通市',py:'zhaotongshi'},{city:'张掖市',py:'zhangyeshi'},{city:'中卫市',py:'zhongweishi'},
			],
			hostCitys:[]
		}
	},
	template:`
		<section class="cityChoiceBox" id="cityChoiceBox">
			<article class="cityChoice-top transit" v-bind:class='{focus:isCancel}'>
				<transition enter-active-class="animated fadeInLeft" leave-active-class="animated fadeOutLeft">
					<span class="fa fa-angle-left" v-if='!isCancel' v-on:click='cityClose'></span>
				</transition>
				<input type="text" class="search-input" placeholder="中文/拼音/首写字母" v-model.trim='ssval' v-on:focus='search' />
				<transition enter-active-class="animated fadeInRight" leave-active-class="animated fadeOutRight">
					<i v-if='isCancel' v-on:click='isCancel=false' class="cancel">取消</i>
				</transition>
			</article>
			<article class="city-Box" v-show='!isCancel'>
				<div class="city-sidaber" id="city-sidaber" v-show='!isCancel'>
					<p v-for="(sidaber,index) in sidabers" v-on:touchstart='mousedownFun(index)' v-on:touchend='mouseupFun(index)' v-text='sidaber'></p>
				</div>
				<div class="host-city">
					<h3>热门城市</h3>
					<span v-for='(item,index) in hostCitys' v-text='item.city' v-on:click='cityactive(index,hostCitys)'></span>
				</div>
				<div class="city-content">
					<h3>A</h3>
					<p v-for='(item,index) in cityA' v-on:click='cityactive(index,cityA)'>{{item.city}}</p>
					<h3>B</h3>
					<p v-for='(item,index) in cityB' v-on:click='cityactive(index,cityB)'>{{item.city}}</p>
					<h3>C</h3>
					<p v-for='(item,index) in cityC' v-on:click='cityactive(index,cityC)'>{{item.city}}</p>
					<h3>D</h3>
					<p v-for='(item,index) in cityD' v-on:click='cityactive(index,cityD)'>{{item.city}}</p>
					<h3>E</h3>
					<p v-for='(item,index) in cityE' v-on:click='cityactive(index,cityE)'>{{item.city}}</p>
					<h3>F</h3>
					<p v-for='(item,index) in cityF' v-on:click='cityactive(index,cityF)'>{{item.city}}</p>
					<h3>G</h3>
					<p v-for='(item,index) in cityG' v-on:click='cityactive(index,cityG)'>{{item.city}}</p>
					<h3>H</h3>
					<p v-for='(item,index) in cityH' v-on:click='cityactive(index,cityH)'>{{item.city}}</p>
					<h3>J</h3>
					<p v-for='(item,index) in cityJ' v-on:click='cityactive(index,cityJ)'>{{item.city}}</p>
					<h3>K</h3>
					<p v-for='(item,index) in cityK' v-on:click='cityactive(index,cityK)'>{{item.city}}</p>
					<h3>L</h3>
					<p v-for='(item,index) in cityL' v-on:click='cityactive(index,cityL)'>{{item.city}}</p>
					<h3>M</h3>
					<p v-for='(item,index) in cityM' v-on:click='cityactive(index,cityM)'>{{item.city}}</p>
					<h3>N</h3>
					<p v-for='(item,index) in cityN' v-on:click='cityactive(index,cityN)'>{{item.city}}</p>
					<h3>P</h3>
					<p v-for='(item,index) in cityP' v-on:click='cityactive(index,cityP)'>{{item.city}}</p>
					<h3>Q</h3>
					<p v-for='(item,index) in cityQ' v-on:click='cityactive(index,cityQ)'>{{item.city}}</p>
					<h3>R</h3>
					<p v-for='(item,index) in cityR' v-on:click='cityactive(index,cityR)'>{{item.city}}</p>
					<h3>S</h3>
					<p v-for='(item,index) in cityS' v-on:click='cityactive(index,cityS)'>{{item.city}}</p>
					<h3>T</h3>
					<p v-for='(item,index) in cityT' v-on:click='cityactive(index,cityT)'>{{item.city}}</p>
					<h3>W</h3>
					<p v-for='(item,index) in cityW' v-on:click='cityactive(index,cityW)'>{{item.city}}</p>
					<h3>X</h3>
					<p v-for='(item,index) in cityX' v-on:click='cityactive(index,cityX)'>{{item.city}}</p>
					<h3>Y</h3>
					<p v-for='(item,index) in cityY' v-on:click='cityactive(index,cityY)'>{{item.city}}</p>
					<h3>Z</h3>
					<p v-for='(item,index) in cityZ' v-on:click='cityactive(index,cityZ)'>{{item.city}}</p>
				</div>
				<div v-show='zimShow' class="zimShow" v-text='zimText'></div>
			</article>
			<article class="search-box" id="search-box" v-show='isCancel'>
				<p class="sousuo" v-for='(item,index) in sousuos' v-on:click='cityactive(index,sousuos)'>{{item.city}}</p>
			</article>
		</section>
	`,
	methods:{
		//调用城市选择组件
		cityFun:function(){
	        var cityChoiceBox=document.getElementById('cityChoiceBox');
	        var citySidaber=document.getElementById('city-sidaber');
	        var clientW=document.documentElement.clientWidth||document.body.clientWidth;
	        cityChoiceBox.style.left=clientW+'px';
	        cityChoiceBox.style.display="block"
	        this.starMove(cityChoiceBox,{left:0},function(){
	        	citySidaber.style.display="block"
	        });
	     },
	     //关闭城市选择组件
	     cityClose:function(){
	     	this.isCancel=false;
	        var cityChoiceBox=document.getElementById('cityChoiceBox');
	        var citySidaber=document.getElementById('city-sidaber');
	        var clientW=document.documentElement.clientWidth||document.body.clientWidth;
	        citySidaber.style.display="none"
	        this.starMove(cityChoiceBox,{left:clientW},function(){
	        	cityChoiceBox.style.display="none"
	        });
	     },
	     //变速运动
	     starMove:function(obj,json,fn){//添加一个回调函数fn
	        function getStyle(obj,attr){
	          if(obj.currentStyle){
	            return obj.currentStyle[attr];
	            }else{
	              return getComputedStyle(obj,false)[attr];
	              }
	    	}
	        clearInterval(obj.timer);
	        obj.timer = setInterval(function(){
	          var flag = true; //假设都到达了目标值
	          for(var attr in json){
	            //1.取当前值
	            var icur = 0;
	            icur = parseInt(getStyle(obj,attr));
	            //2.算速度
	            var speed = (json[attr] - icur)/8;
	            speed = speed>0?Math.ceil(speed):Math.floor(speed);
	            //3.检查停止
	            if(icur != json[attr]){
	              flag = false;
	            }
	            obj.style[attr] = icur + speed + "px";
	            if(flag){
	              clearInterval(obj.timer);
	              if(fn){//判断是否存在回调函数,并调用
	                fn();
	                }
	              }
	            }
	        },20);
	    },
	    //搜索框获取焦点进入搜索层
	    search:function(){
	    	this.isCancel=true;
	    },
	    //热门城市
	    hostCityss:function(){
			var j=0;
			for(var i=0;i<12;i++){
               Vue.set(this.hostCitys,j,this.citys[i]);
               j++
         	}
		},
		cityactive:function(index,cityss){
			this.cityClose();
			this.zimShow=false;
			this.$emit("tochildevent",cityss[index].city)
		},
		//侧栏字母鼠标按下事件
		mousedownFun:function(index){
			this.zimShow=!this.zimShow;
			this.zimText=this.sidabers[index]
		},
		//侧栏字母鼠标弹起事件
		mouseupFun:function(index){
			this.zimShow=!this.zimShow;
			var cityChoiceBox=document.getElementById('cityChoiceBox');
			var h3=cityChoiceBox.getElementsByTagName('h3');
			var timer = null;	
			function scrT(iTarget){
				clearInterval(timer);
				document.ontouchstart=function(){
					clearInterval(timer);
				}
				timer = setInterval(function(){
				var scrollT = document.documentElement.scrollTop || document.body.scrollTop,
				    speed = 0;
				speed = Math.floor((iTarget - scrollT)/5);
				if(scrollT == iTarget){
					clearInterval(timer);
					}else{
						document.documentElement.scrollTop = scrollT + speed;
						document.body.scrollTop = scrollT + speed;
						}
				},30);
			}
			scrT(h3[index].offsetTop-50);
		}
	},
	//首字母过滤
	computed: {
        cityA: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='a';
            });
        },
        cityB: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='b';
            });
        },
        cityC: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='c';
            });
        },
        cityD: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='d';
            });
        },
        cityE: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='e';
            });
        },
        cityF: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='f';
            });
        },
        cityG: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='g';
            });
        },
        cityH: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='h';
            });
        },
        cityJ: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='j';
            });
        },
        cityK: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='k';
            });
        },
        cityL: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='l';
            });
        },
        cityM: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='m';
            });
        },
        cityN: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='n';
            });
        },
        cityP: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='p';
            });
        },
        cityQ: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='q';
            });
        },
        cityR: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='r';
            });
        },
        cityS: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='s';
            });
        },
        cityT: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='t';
            });
        },
        cityW: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='w';
            });
        },
        cityX: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='x';
            });
        },
        cityY: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='y';
            });
        },
        cityZ: function () {
            return this.citys.filter(function (item) {
                return item.py.substr(0, 1)=='z';
            });
        },
        //搜索过滤
        sousuos:function(){
        	var ssval = this.ssval;
        	return this.citys.filter(function (item) {
                return item.py.indexOf(ssval)!= -1 || item.city.indexOf(ssval) != -1;
            });
        }
    },
	mounted:function(){
		window.addEventListener('load',function(){
			var searchBox=document.getElementById('search-box');
			var srollH=document.documentElement.scrollHeight;
			searchBox.style.height=srollH+'px';
			
		})
		this.hostCityss();
	}

})