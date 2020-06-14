// 
import { encryptMobile, encryptBankCard } from "@/utils/util";
const property = {
  methods: {
    /**
     * 组装账户列表
     * @param {Array} list 列表
     * @param {Boolean} noAddWx 是否添加微信账号，默认为自动添加（前提有openid）
     */
    packageAccountList(list, noAddWx) {
      let arr = list.map(e => {
        e.title = e.realname;
        e.label = encryptMobile(e.account_number);
        e.showLabel = e.account_number;
        if (e.type == 3) {
          e.logo = this.$BASEIMGPATH + "icon-alipay.png";
        }
        if (e.type == 4) {
          e.title = e.open_bank;
          e.logo = this.$BASEIMGPATH + "icon-yl.png";
        }
        if (e.type == 1) {
          let cardType = e.bank_type == "00" ? "储蓄卡" : "信用卡";
          e.title = e.open_bank + " (" + cardType + ")";
          e.label = encryptBankCard(e.account_number);
          e.showLabel = e.account_number;
          e.logo = e.bank_iocn;
        }
        return e;
      });
      if (!noAddWx && this.$store.state.member.info.wx_openid) {
        arr.unshift({
          title: "微信账号",
          type: 2,
          id: "-2",
          logo: this.$BASEIMGPATH + "icon-wechat.png",
          label: "微信授权登录后自动增加"
        });
      }
      return arr;
    },
  }
};

export default property
