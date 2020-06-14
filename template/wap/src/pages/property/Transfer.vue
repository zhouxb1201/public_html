<template>
  <Layout ref="load" class="property-transfer bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell-group>
      <van-cell :title="paramText.usable" class="cell-panel">
        <span class="text-maintone">{{paramText.number}}</span>
      </van-cell>
      <van-cell :title="paramText.typeText" class="cell-panel">
        <van-radio-group v-model="type" class="cell-radio-group" @change="change">
          <van-radio :name="1">{{paramText['1'].user}}</van-radio>
          <van-radio :name="2">{{paramText['2'].user}}</van-radio>
        </van-radio-group>
      </van-cell>
      <van-field
        :label="paramText[type].user"
        type="number"
        v-model.number="params[paramText[type].name]"
        :placeholder="paramText[type].userPlaceholder"
        clearable
      />
      <van-field
        :label="paramText.input"
        type="number"
        v-model.number="params.money"
        :placeholder="paramText.inputPlaceholder"
        @input="onInput"
        clearable
      />
      <van-field
        :label="paramText.remark"
        :placeholder="paramText.remarkPlaceholder"
        v-model="params.remark"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :loading="isLoading"
        @click="onTransfer"
      >{{pageTypeText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      type="3"
      :money="params.money"
      @confirm="onPayPassword"
      @cancel="isLoading=false"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { yuan } from "@/utils/filter";
import { payPassword } from "@/mixins";
import DialogPayPassword from "@/components/DialogPayPassword";
import { TRANSFER_BALANCE } from "@/api/property";
import { validMobile } from "@/utils/validator";
export default sfc({
  name: "property-transfer",
  data() {
    return {
      type: 1,
      params: {
        money: null,
        remark: null
      },
      isLoading: false
    };
  },
  computed: {
    navbarTitle() {
      const { balance_style } = this.$store.state.member.memberSetText;
      let title = balance_style + this.pageTypeText;
      document.title = title;
      return title;
    },
    pageTypeText() {
      return "转账";
    },
    balanceText() {
      return this.$store.state.member.memberSetText.balance_style;
    },
    paramText() {
      return {
        typeText: this.pageTypeText + "方式",
        usable: "可用" + this.balanceText,
        number: yuan(this.$store.state.member.info.balance),
        1: {
          name: "user_id",
          user: "会员ID",
          userPlaceholder: "请输入收款人ID"
        },
        2: {
          name: "mobile",
          user: "手机号码",
          userPlaceholder: "请输入会员手机号码"
        },
        input: this.pageTypeText + this.balanceText,
        inputPlaceholder: "请输入" + this.pageTypeText + this.balanceText,
        remark: "备注",
        remarkPlaceholder: "选填"
      };
    }
  },
  mixins: [payPassword],
  mounted() {
    if (this.$store.getters.config.is_transfer) {
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({
        errorType: "fail",
        errorText: "未开启" + this.balanceText + this.pageTypeText,
        showFoot: false
      });
    }
  },
  methods: {
    change(e) {
      if (e == 1) {
        this.params.mobile && delete this.params.mobile;
      } else {
        this.params.user_id && delete this.params.user_id;
      }
    },
    onInput(e) {
      let money = parseFloat(this.$store.state.member.info.balance);
      let value = parseFloat(e);
      value && value > money && (this.params.money = money);
    },
    onPayPassword(e) {
      this.password = e;
      this.onTransfer();
    },
    onTransfer() {
      if (this.type == 2 && !validMobile(this.params.mobile)) {
        return false;
      }
      this.isLoading = true;
      this.validPayPassword(this.password)
        .then(() => {
          TRANSFER_BALANCE(this.params)
            .then(({ data }) => {
              this.$router.back();
              this.$Toast.success(this.pageTypeText + "成功");
              this.isLoading = false;
            })
            .catch(() => {
              this.isLoading = false;
            });
        })
        .catch(() => {
          this.password = "";
        });
    }
  },
  components: {
    DialogPayPassword
  }
});
</script>

<style scoped>
</style>
