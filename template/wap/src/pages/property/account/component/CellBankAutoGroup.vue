<template>
  <div>
    <van-cell-group :border="false">
      <CellSelectBank @select="onSelectBank" />
      <van-field
        required
        label="银行卡号"
        text="number"
        placeholder="请输入银行卡号"
        v-model="params.account_number"
      />
      <van-field required label="持卡人" placeholder="请输入持卡人姓名" v-model="params.realname" />
      <van-field required label="身份证号" placeholder="请输入身份证号" v-model="params.bank_card" />
      <van-field
        required
        label="手机号"
        text="number"
        maxlength="11"
        placeholder="银行预留的手机号"
        v-model="params.mobile"
      >
        <van-icon
          name="warning-o"
          slot="right-icon"
          size="20px"
          color="#1989fa"
          @click="onShowExample('mobile')"
          v-if="isCredit"
        />
      </van-field>
      <template v-if="isCredit">
        <van-field
          required
          label="有效期"
          text="number"
          maxlength="4"
          placeholder="示例：01/20，输入0120"
          v-model="params.valid_date"
        >
          <van-icon
            name="warning-o"
            slot="right-icon"
            size="20px"
            color="#1989fa"
            @click="onShowExample('valid_date')"
          />
        </van-field>
        <van-field
          required
          label="安全码"
          text="number"
          maxlength="3"
          placeholder="卡背后三位数"
          v-model="params.cvv2"
        >
          <van-icon
            name="warning-o"
            slot="right-icon"
            size="20px"
            color="#1989fa"
            @click="onShowExample('cvv2')"
          />
        </van-field>
      </template>
    </van-cell-group>
    <van-popup v-model="showExamplePopup" class="popup-box" v-if="isCredit">
      <div v-if="showExample">
        <div class="example-title">{{exampleData[showExample].title}}</div>
        <img
          class="example-img"
          :src="exampleData[showExample].img"
          v-if="exampleData[showExample].img"
        />
        <div class="example-text">{{exampleData[showExample].text}}</div>
      </div>
    </van-popup>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="save" :loading="loading">验证并保存</van-button>
    </div>
  </div>
</template>

<script>
import CellSelectBank from "./CellSelectBank";
import { validCard, validMobile } from "@/utils/validator";
export default {
  data() {
    return {
      isCredit: false, //是否为信用卡
      showExamplePopup: false,
      showExample: "",
      exampleData: {
        valid_date: {
          title: "有效期说明",
          img: this.$BASEIMGPATH + "bank-demo-01.png",
          text:
            "有效期是打印在信用卡正面卡号下方。标准格式为月份在前，年份在后的一串数字。"
        },
        cvv2: {
          title: "安全码说明",
          img: this.$BASEIMGPATH + "bank-demo-02.png",
          text: "安全码是卡背面签名区的一组数字，一般为末位三位数字。"
        },
        mobile: {
          title: "预留手机说明",
          img: "",
          text:
            "银行预留的手机号码是办理该银行卡时所填写的手机行号。没有预留、手机号码忘记或者手机号码停用，请联系银行客服更新处理。"
        }
      }
    };
  },
  props: {
    params: Object,
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onSelectBank({ bank_type, bank_code }) {
      this.params.bank_type = bank_type;
      this.params.bank_code = bank_code;
      if (bank_type == "02") {
        this.isCredit = true;
      } else {
        this.isCredit = false;
        delete this.params.cvv2;
        delete this.params.valid_date;
      }
    },
    // 显示示例
    onShowExample(action) {
      this.showExamplePopup = true;
      this.showExample = action;
    },
    // 验证
    validator() {
      const params = this.params;
      if (!params.bank_type) {
        this.$Toast("请选择银行！");
        return false;
      }
      if (!params.account_number) {
        this.$Toast("请输入银行卡号！");
        return false;
      }
      if (!params.realname) {
        this.$Toast("请输入持卡人姓名！");
        return false;
      }
      if (!validCard(params.bank_card)) {
        return false;
      }
      if (!validMobile(params.mobile)) {
        return false;
      }
      if (this.isCredit) {
        if (!params.valid_date) {
          this.$Toast("请输入银行卡有效期！");
          return false;
        }
        if (!params.cvv2) {
          this.$Toast("请输入银行卡安全码！");
          return false;
        }
      }
      return params;
    },
    save() {
      const params = this.validator();
      if (params) {
        this.$emit("save", params);
      }
    }
  },
  components: {
    CellSelectBank
  }
};
</script>

<style scoped>
.popup-box {
  width: 80%;
  max-height: 60%;
  background: transparent;
}

.example-img {
  max-width: 100%;
  display: block;
  height: auto;
}

.example-title {
  padding: 15px 10px;
  border-bottom: 1px solid #fff;
  text-align: center;
  font-size: 16px;
  color: #fff;
  margin-bottom: 15px;
}

.example-text {
  color: #fff;
  line-height: 1.4;
  margin-top: 15px;
}
</style>