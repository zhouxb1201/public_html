let TIM = require("./tim-wx.js");
let COS = require("./cos-wx-sdk-v5.js");


let options = {
  SDKAppID: 1400329035 // 接入时需要将 0 替换为您的云通信应用的 SDKAppID
};
// 创建 SDK 实例，`TIM.create()`方法对于同一个 `SDKAppID` 只会返回同一份实例
let tim = TIM.create(options); // SDK 实例通常用 tim 表示

// 设置 SDK 日志输出级别，详细分级请参见 setLogLevel 接口的说明
tim.setLogLevel(0); // 普通级别，日志量较多，接入时建议使用
// tim.setLogLevel(1); // release级别，SDK 输出关键信息，生产环境时建议使用

// 注册 COS SDK 插件
tim.registerPlugin({ 'cos-wx-sdk': COS });

// 接下来可以通过 tim 进行事件绑定和构建 IM 应用
// 监听事件，如：
tim.on(TIM.EVENT.SDK_READY, function (event) {
  // 收到离线消息和会话列表同步完毕通知，接入侧可以调用 sendMessage 等需要鉴权的接口
  // event.name - TIM.EVENT.SDK_READY
});

module.exports = {
  tim: tim
}









