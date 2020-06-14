import http from '@/utils/request'

// 获取数字资产设置
export function GET_BLOCKCHAINSET(data) {
  return http({
    url: '/addons/blockchain/blockchain/blockChainSet',
    method: 'post',
    data
  })
}

// 获取eth资产信息
export function GET_BLOCKCHAINETHINFO(data) {
  return http({
    url: '/addons/blockchain/blockchain/getEthWallet',
    method: 'post',
    data
  })
}

// 获取eos资产信息
export function GET_BLOCKCHAINEOSINFO(data) {
  return http({
    url: '/addons/blockchain/blockchain/getEosWallet',
    method: 'post',
    data
  })
}

// 创建eth账户
export function CREAT_BLOCKCHAINETHACCOUNT(password) {
  return http({
    url: '/addons/blockchain/blockchain/createEthWallet',
    method: 'post',
    data: { password },
    isWriteIn: true,
    isShowLoading: true,
    timeout: 0,
    loadingText: '创建中'
  })
}

// 获取gas费用
export function GET_BLOCKCHAINETHGAS(data) {
  return http({
    url: '/addons/blockchain/blockchain/ethGas',
    method: 'post',
    data
  })
}

// 检测eth钱包地址是否存在
export function CHECK_BLOCKCHAINETHADDRESS(address) {
  return http({
    url: '/addons/blockchain/blockchain/checkEthAddress',
    method: 'post',
    data: { address },
    isShowLoading: true,
    loadingText: '验证地址中'
  })
}

// 交易明细
export function GET_BLOCKCHAINLOGLIST(data) {
  return http({
    url: '/addons/blockchain/blockchain/memberBlockChainRecord',
    method: 'post',
    data,
  })
}

// 交易明细详情
export function GET_BLOCKCHAINLOGDETAIL(id) {
  return http({
    url: '/addons/blockchain/blockchain/memberBlockChainRecordDetail',
    method: 'post',
    data: { id }
  })
}

// 验证eos账号是否存在
export function CHECK_BLOCKCHAINEOSACCOUNTNAME(account_name) {
  return http({
    url: '/addons/blockchain/blockchain/checkEosAccountName',
    method: 'post',
    data: { account_name }
  })
}

// 创建eos钱包(购买内存)获取预订单交易号
export function CREATE_BLOCKCHAINEOSWALLET(data) {
  return http({
    url: '/addons/blockchain/blockchain/createEosWallet',
    method: 'post',
    data,
    timeout: 0,
    isWriteIn: true
  })
}

// 创建eos钱包(无需购买内存)
export function CREATE_BLOCKCHAINEOSWALLETUNPAY(data) {
  return http({
    url: '/addons/blockchain/blockchain/createEosWalletUnPay',
    method: 'post',
    data,
    timeout: 0,
    isWriteIn: true
  })
}

// 创建eos钱包余额支付
export function PAY_BLOCKCHAINEOSBALANCEPAY(out_trade_no) {
  return http({
    url: '/addons/blockchain/blockchain/balancePay',
    method: 'post',
    data: { out_trade_no },
    timeout: 0,
    isWriteIn: true
  })
}

// eth/eos兑换
export function EXCHANGE_BLOCKCHAIN(type, data) {
  return http({
    url: '/addons/blockchain/blockchain/' + type + 'Exchange',
    method: 'post',
    data,
    isShowLoading: true,
    loadingText: '申请兑换中',
    timeout: 0,
    isWriteIn: true
  })
}

// eth/eos转账
export function TRANSFER_BLOCKCHAIN(type, data) {
  return http({
    url: '/addons/blockchain/blockchain/' + type + 'Transfer',
    method: 'post',
    data,
    isShowLoading: true,
    loadingText: '申请转账中',
    timeout: 0,
    isWriteIn: true
  })
}

// 导出eth/eos  keystore/私钥
export function EXPORT_BLOCKCHAINKEY({ type, key }, data) {
  let api = ''
  if (type == 'eth') {
    api = key == 'keystore' ? 'exportEthKeyStore' : 'exportEthPrivateKey'
  } else {
    api = key == 'keystore' ? 'exportEosKeyStore' : 'exportEosPrivateKey'
  }
  return http({
    url: '/addons/blockchain/blockchain/' + api,
    method: 'post',
    data,
    isShowLoading: true,
    loadingText: '导出中',
    isWriteIn: true
  })
}

// 获取eth/eos图表
export function GET_BLOCKCHAINCHART(type, timeType) {
  return http({
    url: '/addons/blockchain/blockchain/' + type + 'MarketInfo',
    method: 'post',
    data: { type: timeType }
  })
}

// 抵押/赎回 eos
export function SUB_BLOCKCHAINRESOURCE({ type, typeText }, data) {
  return http({
    url: '/addons/blockchain/blockchain/' + type,
    method: 'post',
    data,
    isShowLoading: true,
    timeout: 0,
    loadingText: typeText + '中',
    isWriteIn: true
  })
}

// 换算 积分和eth、eos
export function COUNT_BLOCKCHAINEXPORT(data) {
  return http({
    url: '/addons/blockchain/blockchain/pointExMoney',
    method: 'post',
    data
  })
}

// 获取相关虚拟币可支付信息
export function GET_BLOCKCHAINPAYINFO(out_trade_no) {
  return http({
    url: '/order/getBlockChainBalance',
    method: 'post',
    data: { out_trade_no }
  })
}