wechat-sdk-php
==============

基于微博SDK改造的微信SDK(PHP版本),封装了微信新开放的九大服务号接口，php版本的sdk，包括分组、获取用户信息、获取关注列表、创建带参数的二维码，上传下载多媒体文件、客服接口。
使用示例：
<ol start="1" class="dp-c"><li><span><span class="vars">$access_token</span><span> = </span><span class="vars">$this</span><span>-&gt;getAccessToken(</span><span class="vars">$Token</span><span>); </span><span class="comment">//获取微信公众号AccessToken</span><span>  </span></span></li><li><span><span class="vars">$wxapi</span><span> = </span><span class="keyword">new</span><span> WxApi(</span><span class="vars">$access_token</span><span>);  </span></span></li><li><span><span class="vars">$wxapi</span><span>-&gt;message_custom_send_text(</span><span class="vars">$openId</span><span>, </span><span class="string">"你好"</span><span>);  </span></span></li></ol>
