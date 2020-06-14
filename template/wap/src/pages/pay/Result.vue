<template>
  <Layout ref="load" class="pay-result bg-f8">
    <Navbar :isMenu="false" :title="navbarTitle" :isShowLeft="false" />
    <ResultBox :state-type="stateType" :message="resultStateMessage">
      <ResultFootGroup :items="footBtnItems" @click="onOperation" />
    </ResultBox>
    <CellAssemble
      class="card-group-box"
      :record_id="info.group_record_id"
      replace
      v-if="showGroup"
    />
    <CellAddWxCard class="card-group-box" :params="info.card_ids" v-if="showAddWxCard" />
    <CellStoreInfo class="card-group-box" :info="info.card_store" v-if="showOffline" />
    <CellMessageTip class="card-group-box" v-if="messageTip" :message="messageTip" />
    <CellAddGift class="card-group-box" v-if="showAddGift" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_PAYRESULT } from "@/api/pay";
import { pushState } from "@/mixins";
import CellAssemble from "../assemble/component/CellAssemble";
import CellStoreInfo from "../consumercard/component/CellStoreInfo";
import CellAddWxCard from "../consumercard/component/CellAddWxCard";
import ResultBox from "@/components/ResultBox";
import ResultFootGroup from "./component/ResultFootGroup";
import CellMessageTip from "./component/CellMessageTip";
import CellAddGift from "./component/CellAddGift";
import { isIos } from "@/utils/util";
import { removeSession, getSession } from "@/utils/storage";
export default sfc({
  name: "pay-result",
  data() {
    return {
      info: {
        pay_status: null
      }
    };
  },
  mixins: [pushState],
  computed: {
    navbarTitle() {
      const info = this.info;
      let title = "";
      if (this.pageType == "blockchain") {
        title = "提交成功";
      } else if (this.pageType == "integral" && info.pay_status == 2) {
        title = "兑换成功";
      } else if (this.pageType == "recharge") {
        if (info.pay_status == 2) {
          title = "充值成功";
        } else if (info.pay_status == 0) {
          title = "充值失败";
        }
      } else if (this.pageType == "dpay" && info.pay_status == 2) {
        title = "提交成功";
      } else {
        if (info.pay_status == 2) {
          title = "支付成功";
        } else if (info.pay_status == 0) {
          title = "支付失败";
        }
      }
      if (title) document.title = title;
      return title;
    },
    /**
     * 页面类型
     * mall ==> 普通
     * recharge ==> 充值
     * channel ==> 渠道商
     * integral ==> 积分
     * microshop ==> 微店
     * group ==> 拼团
     * store ==> 门店
     * offline ==> 线下
     * paygift ==> 支付有礼
     * blockchain ==> 区块链钱包
     * dpay ==> 货到付款
     */
    pageType() {
      const info = this.info;
      const query = this.$route.query;
      let name = "";
      if (info.order_from == 2) {
        name = "recharge";
      } else if (info.is_channel) {
        name = "channel";
      } else if (info.is_integral_order || query.is_integral_order) {
        name = "integral";
      } else if (
        info.order_type == 2 ||
        info.order_type == 3 ||
        info.order_type == 4 ||
        info.order_type == 11
      ) {
        name = "microshop";
      } else if (info.group_record_id) {
        name = "group";
      } else if (info.shipping_type == 2) {
        name = "store";
      } else if (info.card_store && info.card_store.store_name) {
        name = "offline";
      } else if (info.pay_gift_status) {
        name = "paygift";
      } else if (query.blockchain_order) {
        name = "blockchain";
      } else if (query.dpay_order) {
        name = "dpay";
      } else {
        name = "mall";
      }
      // console.log(name);
      return name;
    },
    resultStateMessage() {
      let message = this.navbarTitle;
      if (this.pageType == "blockchain") {
        message = "链上交易处理中";
      } else if (this.pageType == "dpay") {
        message = "订单提交成功";
      }
      return message;
    },
    // 结果状态
    stateType() {
      let type = "";
      if (this.pageType == "blockchain") {
        type = "pay-success";
      } else if (this.pageType == "recharge") {
        type = this.info.pay_status == 2 ? "recharge-success" : "recharge-fail";
      } else {
        type = this.info.pay_status == 2 ? "pay-success" : "pay-fail";
      }
      return type;
    },
    showGroup() {
      const info = this.info;
      return (
        this.pageType == "group" && info.pay_status == 2 && info.group_record_id
      );
    },
    showAddWxCard() {
      const info = this.info;
      return (
        this.pageType == "offline" &&
        info.pay_status == 2 &&
        !info.wx_card_state
      );
    },
    showOffline() {
      const info = this.info;
      return (
        this.pageType == "offline" && info.pay_status == 2 && info.card_store
      );
    },
    showAddGift() {
      const info = this.info;
      return (
        this.pageType == "paygift" &&
        info.pay_status == 2 &&
        info.pay_gift_status == 1
      );
    },
    messageTip() {
      let text = "";
      if (this.pageType == "store" && this.info.pay_status == 2) {
        text =
          "O2O订单请前往“订单列表”或“订单详情”查看核销码到对应门店进行核销。";
      } else if (this.pageType == "blockchain") {
        text =
          "虚拟货币交易需要等区块链上处理完成才算支付成功，支付成功后商城才能安排发货，请耐心等待并关注订单状态。";
      }
      return text;
    },
    footBtnItems() {
      const info = this.info;
      const type = this.pageType;
      let arr = [];

      if (type == "channel") {
        // 渠道商相关操作
        arr.push(
          { text: "微商中心", to: "/channel/centre" },
          { text: "查看订单", to: "/channel/order/list/" + info.is_channel }
        );
      } else if (type == "integral") {
        //积分商城相关操作
        arr.push(
          { text: "继续兑换", to: "/integral/index" },
          { text: "查看订单", to: "/order/list" }
        );
      } else if (type == "microshop") {
        // 微店相关操作
        if (info.pay_status != 2) {
          arr.push({ text: "重新支付", action: "againPay", event: true });
        }
        arr.push({
          text: info.pay_status == 2 ? "前往微店" : "返回微店",
          to: "/microshop/centre"
        });
      } else if (type == "recharge") {
        // 充值相关操作
        if (info.pay_status == 2) {
          arr.push({ text: "继续购物", to: "/" });
        } else {
          arr.push({
            text: "重新支付",
            to: `/pay/payment?out_trade_no=${this.$route.query.out_trade_no}#recharge`
          });
        }
        arr.push({
          text: "账号" + this.$store.state.member.memberSetText.balance_style,
          to: "/property/balance"
        });
      } else if (type == "blockchain") {
        // 区块链相关操作
        arr.push(
          {
            text: "继续购物",
            to: "/"
          },
          {
            text: "查看订单",
            to: "/order/list"
          }
        );
      } else if (type == "dpay") {
        // 货到付款
        arr.push(
          {
            text: "继续购物",
            to: "/"
          },
          {
            text: "查看订单",
            to: "/order/list"
          }
        );
      } else {
        // 普通下单相关操作
        if (info.pay_status == 2) {
          arr.push({ text: "继续购物", to: "/" });
        } else {
          arr.push({ text: "重新支付", action: "againPay", event: true });
        }
        arr.push({
          text: "查看订单",
          to: info.order_id ? "/order/detail/" + info.order_id : "/order/list"
        });
      }
      return arr;
    }
  },
  mounted() {
    const {
      is_integral_order,
      pay_status,
      blockchain_order,
      dpay_order
    } = this.$route.query;
    if (is_integral_order) {
      this.info.is_integral_order = is_integral_order;
      this.info.pay_status = pay_status;
      this.$refs.load.success();
    } else if (blockchain_order) {
      this.info.pay_status = 2;
      this.$refs.load.success();
    } else if (dpay_order) {
      this.info.pay_status = 2;
      this.$refs.load.success();
    } else {
      this.loadData();
    }
  },
  methods: {
    loadData() {
      GET_PAYRESULT(this.$route.query.out_trade_no)
        .then(({ data }) => {
          this.info = data;
          if (getSession("shopkeeper_id") && data.pay_status == 2) {
            removeSession("shopkeeper_id");
          }
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    goBack() {
      this.$router.go(-3);
    },
    onOperation({ event, action }) {
      if (event && action == "againPay") {
        const out_trade_no = this.$route.query.out_trade_no;
        if (isIos()) {
          location.replace(
            `${this.$store.state.domain}/wap/pay/payment?out_trade_no=${out_trade_no}`
          );
        } else {
          this.$router.replace({
            name: "pay-payment",
            query: { out_trade_no }
          });
        }
      }
    }
  },
  components: {
    CellAssemble,
    CellStoreInfo,
    CellAddWxCard,
    ResultBox,
    ResultFootGroup,
    CellMessageTip,
    CellAddGift
  }
});
</script>

<style scoped>
</style>
