<template>
  <div class="order-apply">
    <div v-if="pageType === 1">
      <van-cell-group>
        <van-cell title="处理方式" class="cell-panel">
          <van-radio-group v-model="default_refund_type" class="cell-radio-group">
            <van-radio :name="1">{{refund_type === 2 ? '仅退款' : '退款'}}</van-radio>
            <van-radio :name="2" v-if="refund_type === 2 && info.goods_type != 3">退货退款</van-radio>
          </van-radio-group>
        </van-cell>
        <CellSelector
          label="退款原因"
          placeholder="请选择退款原因"
          :value="reasonText"
          :columns="columns"
          popup-title="退款原因"
          @confirm="onPicker"
        />
        <van-field
          v-if="info.refund_max_money>0"
          label="退款金额"
          v-model.number="refund_require_money"
          type="number"
          clearable
          :placeholder="'最多能退款¥'+ info.refund_max_money"
        />
        <van-field
          v-if="info.refund_point>0"
          :label="'退'+$store.state.member.memberSetText.point_style"
          disabled
          :value="info.refund_point"
        />
        <template v-for="(bi,b) in refundPaymentType">
          <van-field
            v-if="info['refund_'+bi+'_money']"
            :label="'退款'+bi.toUpperCase()"
            readonly
            :value="info['refund_'+bi+'_money']"
          />
          <van-field
            v-if="info['refund_'+bi+'_charge']"
            label="手续费"
            readonly
            :value="info['refund_'+bi+'_charge']"
          />
          <van-field
            v-if="info['refund_'+bi+'_val']"
            label="实际到账"
            readonly
            :value="info['refund_'+bi+'_val']"
          />
        </template>
      </van-cell-group>
      <div class="tip-text" v-if="blockchainText">{{blockchainText}}</div>
      <div class="foot-btn-group">
        <van-button
          size="normal"
          block
          round
          type="danger"
          :disabled="isDisabledRefundBtn"
          :loading="isLoading"
          @click="onSubmit"
        >{{refundBtnText}}</van-button>
      </div>
    </div>
    <div v-else-if="pageType === 2">
      <div v-if="info.refund_status === 1">
        <van-cell-group>
          <van-field label="处理方式" readonly :value="info.refund_type === 2 ? '退货退款' : '退款'" />
          <van-field
            label="退款原因"
            readonly
            :value="info.refund_reason ? refund_reason_text : reasonText"
          />
          <van-field
            v-if="info.require_refund_money>0"
            label="退款金额"
            readonly
            :value="(info.require_refund_money || refund_require_money) | yuan"
          />
          <van-field
            v-if="info.refund_point>0"
            :label="'退'+$store.state.member.memberSetText.point_style"
            readonly
            :value="info.refund_point"
          />
          <template v-for="(bi,b) in refundPaymentType">
            <van-field
              v-if="info['refund_'+bi+'_money']"
              :label="'退款'+bi.toUpperCase()"
              readonly
              :value="info['refund_'+bi+'_money']"
            />
            <van-field
              v-if="info['refund_'+bi+'_charge']"
              label="手续费"
              readonly
              :value="info['refund_'+bi+'_charge']"
            />
            <van-field
              v-if="info['refund_'+bi+'_val']"
              label="实际到账"
              readonly
              :value="info['refund_'+bi+'_val']"
            />
          </template>
        </van-cell-group>
        <div class="foot-btn-group">
          <van-button
            size="normal"
            block
            round
            type="danger"
            :loading="isLoading"
            @click="onCancel"
          >取消{{info.refund_type === 2 ? '退货退款' : '退款'}}</van-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { APPLY_REFUNDASK, CANCEL_REFUNDASK } from "@/api/order";
import CellSelector from "@/components/CellSelector";
export default {
  data() {
    return {
      refund_require_money: null,
      reasonText: "",
      reason_id: "",

      isLoading: false,

      default_refund_type: 1,
      columns: [
        {
          text: "拍错/多拍/不想要",
          id: 1
        },
        {
          text: "协商一致退款/退货",
          id: 2
        },
        {
          text: "缺货",
          id: 3
        },
        {
          text: "未按约定时间发货",
          id: 4
        },
        {
          text: "其他",
          id: 5
        }
      ]
    };
  },
  props: {
    pageType: {
      type: Number
    },
    info: {
      type: Object
    }
  },
  computed: {
    refund_type() {
      const order_status = this.info.order_status;
      return order_status == 2 || order_status == 3 || order_status == 4
        ? 2
        : 1;
    },
    // 退款退货原因
    refund_reason_text() {
      const $this = this;
      const obj = $this.columns.filter(e => {
        return e.id == $this.info.refund_reason;
      })[0];
      return obj.text;
    },
    refundPaymentType() {
      let arr = [];
      if (this.info.refund_eth_money) {
        arr.push("eth");
      }
      if (this.info.refund_eos_money) {
        arr.push("eos");
      }
      return arr;
    },
    blockchainText() {
      return this.refundPaymentType.length
        ? "由于行情不断更新，实际到账金额可能会有细小偏差"
        : "";
    },
    // 全额退款，不允许输入金额
    // isReadonlyRefundMoney() {
    //   return this.info.is_refund_all ? true : false;
    // },
    isDisabledRefundBtn() {
      // eth和eos状态为true时不能进行退款
      return this.info.eth_status && this.info.eos_status;
    },
    refundBtnText() {
      return this.isDisabledRefundBtn ? "退款金额为0，无法申请退款" : "提交";
    }
  },
  created() {
    this.refund_require_money = this.info.refund_max_money;
  },
  methods: {
    onPicker(value) {
      this.reasonText = value.text;
      this.reason_id = value.id;
    },
    onSubmit() {
      const $this = this;
      const info = $this.info;
      if (!$this.reason_id) {
        $this.$Toast("请选择退款原因！");
        return false;
      }
      if (parseFloat(info.refund_max_money) > 0) {
        if ($this.refund_require_money == null) {
          $this.$Toast("退款金额不能为空！");
          return false;
        }
        if ($this.refund_require_money > info.refund_max_money) {
          $this.$Toast("退款金额不能超过最大退款金额！");
          return false;
        }
        if ($this.refund_require_money < 0) {
          $this.$Toast("退款金额不能小于0！");
          return false;
        }
      } else {
        $this.refund_require_money = 0;
      }
      let order_goods_id = [];
      info.goods_list.forEach(e => {
        order_goods_id.push(e.order_goods_id);
      });
      const params = {};
      params.order_id = info.order_id;
      params.order_goods_id = order_goods_id;
      params.refund_type = $this.default_refund_type;
      params.refund_require_money = $this.refund_require_money;
      params.refund_reason = $this.reason_id;
      // console.log(params);
      // return;
      $this.isLoading = true;
      APPLY_REFUNDASK(params)
        .then(res => {
          $this.$Toast.success("提交成功");
          $this.$router.back();
        })
        .catch(() => {
          $this.isLoading = false;
        });
    },
    onCancel() {
      const $this = this;
      const info = $this.info;
      const msgText = info.refund_type === 2 ? "退货" : "退款";
      $this.$Dialog
        .confirm({
          message: `确认取消${msgText}吗？`
        })
        .then(() => {
          let order_goods_id = [];
          info.goods_list.forEach(e => {
            order_goods_id.push(e.order_goods_id);
          });
          const params = {};
          params.order_id = info.order_id;
          params.order_goods_id = order_goods_id;
          // console.log(params);
          $this.isLoading = true;
          CANCEL_REFUNDASK(params)
            .then(res => {
              $this.$Toast.success("取消成功");
              $this.$router.replace({
                name: "order-list"
              });
            })
            .catch(() => {
              $this.isLoading = false;
            });
        });
    }
  },
  components: {
    CellSelector
  }
};
</script>

<style scoped>
.tip-text {
  margin: 10px 15px;
  font-size: 12px;
  color: #ff454e;
}
</style>
