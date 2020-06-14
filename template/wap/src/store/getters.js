const getters = {
  config: state => state.config.base,
  // 商城运营状态
  wap_status: state => state.config.wap_status,
  token: state => state.user.token,
  areaList: state => state.area.list,
  // 推广码（自身推广码）
  extend_code: state => state.extend.self_code,
  // 分享进来的推广码（上级推广码）
  sup_code: state => {
    let code = null;
    // 如url存在多个推广码情况，获取到的推广码为数组，所以取数组最后一个值
    if (Array.isArray(state.extend.sup_code)) {
      code = state.extend.sup_code[state.extend.sup_code.length - 1];
    } else {
      code = state.extend.sup_code;
    }
    return code;
  },
  // 是否绑定过手机
  isBindMobile: state => state.user.isBindMobile,
  /**
   * 需要进行绑定手机状态
   * 由后台设置账号体系的状态 account_type为3时，
   * 并且开启需要绑定手机is_bind_phone的状态为1时，则需要进行绑定手机的判断
   * account_type 为0/1/2，则需要进行绑定手机的判断
   */
  isBingFlag: state => {
    return !(state.config.base && (state.config.base.account_type === 3 && !state.config.base.is_bind_phone))
  }
}

export default getters
