<template>
  <div v-if="tax_fee.is_pt_show || tax_fee.is_zy_show">
    <van-cell :title="title" is-link :value="value" @click="show=true" />
    <div>
      <PopupBottom v-model="show" :title="title">
        <div class="cell-group">
          <div class="cell">
            <h4>发票类型</h4>
            <div class="check-wrap">
              <span
                :class="is_bill_type_active == 1 ? 'active' : ''"
                v-if="tax_fee.is_pt_show"
                @click="checkBillType(1)"
              >电子普通发票</span>
              <span
                :class="is_bill_type_active == 2 ? 'active' : ''"
                v-if="tax_fee.is_zy_show"
                @click="checkBillType(2)"
              >增值税专用发票</span>
            </div>
            <div class="text">电子普通发票和纸质普通发票具备同等法律效力，可支持报销入账，发票开具后可在订单详情页查看。</div>
          </div>

          <div class="cell" v-if="is_bill_type_active == 1">
            <h4>发票抬头</h4>
            <div class="check-wrap">
              <span
                :class="is_bill_head_active == index ? 'active' : ''"
                v-for="(item,index) in bill_head_list"
                :key="index"
                @click="checkBillHead(index)"
              >{{item.title}}</span>
            </div>
            <div class="input">
              <van-field v-model="headername" :border="false" label="抬头名称" placeholder="请输入抬头名称" />
              <van-field
                v-model="head_taxpayer_no"
                v-if="is_bill_head_active == 1"
                :border="false"
                label="纳税人识别号"
                placeholder="请输入纳税人识别号"
              />
            </div>
          </div>
          <div class="cell" v-if="is_bill_type_active == 2">
            <h4>公司信息</h4>
            <div class="input">
              <van-field v-model="company_name" :border="false" label="公司名称" placeholder="请输入公司名称" />
              <van-field
                v-model="com_taxpayer_no"
                :border="false"
                label="纳税人识别号"
                placeholder="请输入纳税人识别号"
              />
              <van-field v-model="logon_addr" :border="false" label="注册地址" placeholder="请输入注册地址" />
              <van-field
                v-model="logon_phone"
                type="number"
                :border="false"
                label="注册电话"
                maxlength="11"
                placeholder="请输入注册电话"
              />
              <van-field v-model="bank" :border="false" label="开户银行" placeholder="请输入开户银行" />
              <van-field
                v-model="card_no"
                type="number"
                :border="false"
                label="银行账户"
                placeholder="请输入银行账户"
              />
            </div>
          </div>

          <div class="cell">
            <h4>发票内容</h4>
            <div class="check-wrap">
              <span
                :class="is_bill_content_active == index ? 'active' : ''"
                v-for="(item,index) in bill_content_list"
                :key="index"
                @click="checkGoodsTilte(index)"
              >{{item.title}}</span>
            </div>
            <div
              class="text"
            >{{is_bill_content_active == 0 ? bill_content_list[0].content : bill_content_list[1].content}}</div>
          </div>
        </div>
        <div slot="footer" class="fixed-foot-btn-group">
          <van-button size="normal" block round plain hairline type="danger" @click="onCancel">不开具发票</van-button>
          <van-button size="normal" block round type="danger" @click="onSure">确定</van-button>
        </div>
      </PopupBottom>
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
export default {
  data() {
    return {
      title: "发票",
      show: false,
      value: "",

      headername: "",
      head_taxpayer_no: "",

      company_name: "",
      com_taxpayer_no: "",
      logon_addr: "",
      logon_phone: "",
      bank: "",
      card_no: "",

      is_bill_type_active: 1,// 1=>电子普通发票,2 =>增值税专用发票

      is_bill_head_active: 0,
      bill_head_list: [
        {
          title: "个人"
        },
        {
          title: "公司"
        }
      ],

      is_bill_content_active: 0,
      bill_content_list: [
        {
          title: "商品明细",
          content:
            "发票内容将显示商品名称与价格信息，发票金额为实际支付金额，不含优惠抵扣金额与运费。"
        },
        {
          title: "商品分类",
          content:
            "发票内容将显示分类名称（例：服装/女装/T恤）与价格信息，发票金额为实际支付金额，不含优惠抵扣金额。"
        }
      ],

      invoice: {},
      is_tax: 1 // 1 =>可以开具发票，2=>不可以开具发票
    };
  },
  props: {
    tax_fee: [Object],
    shop_id: [Number],
    price: [Number]
  },
  computed: {},
  mounted() {
    this.isBillType();
  },
  methods: {
    isBillType() {
      if (this.tax_fee.is_pt_show && !this.tax_fee.is_zy_show) {
        this.is_bill_type_active = 1; 
        this.value = "不开具发票";
      } else if (!this.tax_fee.is_pt_show && this.tax_fee.is_zy_show) {
        this.is_bill_type_active = 2;
        this.value = "不开具发票";
      } else if (this.tax_fee.is_pt_show && this.tax_fee.is_zy_show) {
        this.is_bill_type_active = 1;
        this.value = "不开具发票";
      }
    },
    onCancel() {
      this.show = false;
      this.value = "不开具发票";
      this.headername = "";
      this.head_taxpayer_no = "";
      this.company_name = "";
      this.com_taxpayer_no = "";
      this.logon_addr = "";
      this.logon_phone = "";
      this.bank = "";
      this.card_no = "";
      this.is_tax = 2;
      this.$emit("getInvoice", this.invoice, this.shop_id, this.is_tax);
    },
    onSure() {
      if (this.is_bill_type_active == 1) {
        this.value = "电子普通发票";
        this.invoice.type = 1;
        if (!this.delSpace(this.headername)) {
          this.$Toast("请输入抬头名称");
          return false;
        }
        this.invoice.title_name = this.headername;
        if (this.is_bill_head_active == 0) {
          this.invoice.title = 1;
        } else {
          this.invoice.title = 2;
          if (!this.delSpace(this.head_taxpayer_no)) {
            this.$Toast("请输入纳税人识别号");
            return false;
          }
          this.invoice.taxpayer_no = this.head_taxpayer_no;
        }
        this.invoice.invoice_tax_key = 'pt';
        this.invoice.price = this.price;
        this.is_tax = 1;
      } else if (this.is_bill_type_active == 2) {
        this.value = "增值税专用发票";
        this.invoice.type = 2;
        if (!this.delSpace(this.company_name)) {
          this.$Toast("请输入公司名称");
          return false;
        } else if (!this.delSpace(this.com_taxpayer_no)) {
          this.$Toast("请输入纳税人识别号");
          return false;
        } else if (!this.delSpace(this.logon_phone)) {
          this.$Toast("请输入注册电话");
          return false;
        } else if (!this.delSpace(this.logon_addr)) {
          this.$Toast("请输入注册地址");
          return false;
        } else if (!this.delSpace(this.bank)) {
          this.$Toast("请输入开户银行");
          return false;
        } else if (!this.delSpace(this.card_no)) {
          this.$Toast("请输入银行账户");
          return false;
        }
        this.invoice.company_name = this.company_name;
        this.invoice.taxpayer_no = this.com_taxpayer_no;
        this.invoice.company_addr = this.logon_addr;
        this.invoice.mobile = this.logon_phone;
        this.invoice.bank = this.bank;
        this.invoice.card_no = this.card_no;
        this.invoice.invoice_tax_key = 'zy';
        this.invoice.price = this.price;
        this.is_tax = 1;
      } else {
        this.value = "不开具发票";
        this.is_tax = 2;
      }
      if (this.is_bill_content_active == 0) {
        this.invoice.content_type = 1;
      } else {
        this.invoice.content_type = 2;
      }
      this.show = false;
      this.$emit("getInvoice", this.invoice, this.shop_id, this.is_tax);
    },
    checkBillType(index) {
      this.is_bill_type_active = index;
    },
    checkBillHead(index) {
      this.is_bill_head_active = index;
    },
    checkGoodsTilte(index) {
      this.is_bill_content_active = index;
    },
    delSpace(value) {
      return value.replace(/\s*/g, "");
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.cell-group {
  margin: 10px 0px;
  padding-bottom: 50px;
}
.cell {
  position: relative;
  margin: 0px 10px;
  padding: 14px 0px;
}
.cell:not(:last-child)::before {
  content: " ";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  left: 0;
  right: 0;
  bottom: 0;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  border-bottom: 1px solid #ebedf0;
}
.check-wrap {
  margin-top: 16px;
  overflow: hidden;
}
.check-wrap span {
  display: inline-block;
  border: 1px solid #dddbdb;
  padding: 4px 10px;
  border-radius: 4px;
  margin-right: 8px;
}
.check-wrap span.active {
  color: #fff;
  background-color: #ff454e;
  border: 1px solid #ff454e;
}
.cell .text {
  font-size: 12px;
  color: #999;
  line-height: 16px;
  padding: 8px 0px 0px 0px;
}
.cell .input {
  margin-top: 10px;
}
.cell .input >>> .van-cell {
  padding: 4px 15px 0px 0px;
}
.fixed-foot-btn-group {
  position: fixed;
  left: 0;
  bottom: 0;
  z-index: 1000;
  display: flex;
  background-color: #fff;
}
.fixed-foot-btn-group >>> .van-button {
  flex: 1;
  margin: 0px 10px;
  height: 40px;
  line-height: 40px;
}
</style>