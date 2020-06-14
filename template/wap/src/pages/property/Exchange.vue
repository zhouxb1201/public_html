<template>
  <Layout ref="load" class="property-exchange bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell-group>
      <van-cell :title="pageTypeText+'类型'" class="cell-panel">
        <van-radio-group v-model="params.types" class="cell-radio-group" @change="change">
          <van-radio :name="1">{{paramText['1'].type}}</van-radio>
          <van-radio :name="2">{{paramText['2'].type}}</van-radio>
        </van-radio-group>
      </van-cell>
      <van-cell :title="paramText[params.types].usable" class="cell-panel">
        <span class="text-maintone">{{paramText[params.types].number}}</span>
      </van-cell>
      <van-field
        :label="paramText[params.types].input"
        type="number"
        :placeholder="paramText[params.types].inputPlaceholder"
        v-model.number="params.money"
        @input="onInput"
        clearable
      />
      <van-field :label="paramText[params.types].output" disabled :value="countExchange" />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :loading="isLoading"
        @click="onExchange"
      >{{pageTypeText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      :type="feeType"
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
import { EXCHANGE_BALANCEPOINT } from "@/api/property";
export default sfc({
  name: "property-exchange",
  data() {
    return {
      params: {
        types: 1,
        money: null
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
      return "兑换";
    },
    feeType() {
      let type = null;
      if (this.params.types == 1) {
        type = 4;
      } else if (this.params.types == 2) {
        type = 5;
      }
      return type;
    },
    balanceText() {
      return this.$store.state.member.memberSetText.balance_style;
    },
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    paramText() {
      return {
        1: {
          type: this.balanceText + "换" + this.pointText,
          usable: "可用" + this.balanceText,
          money: parseFloat(this.$store.state.member.info.balance),
          number: yuan(this.$store.state.member.info.balance),
          input: this.balanceText,
          inputPlaceholder:
            "请输入" + this.pageTypeText + "的" + this.balanceText,
          output: this.pointText
        },
        2: {
          type: this.pointText + "换" + this.balanceText,
          usable: "可用" + this.pointText,
          money: parseInt(this.$store.state.member.info.point),
          number: parseInt(this.$store.state.member.info.point),
          input: this.pointText,
          inputPlaceholder:
            "请输入" + this.pageTypeText + "的" + this.pointText,
          output: this.balanceText
        }
      };
    },
    countExchange() {
      const rate = parseFloat(this.$store.getters.config.convert_rate) || 1;
      let num = null;
      let { types, money } = this.params;
      if (money > 0) {
        if (types == 1) {
          num = Math.floor(money * rate);
        } else {
          num = (money / rate).toFixed(2);
        }
      }
      return num;
    }
  },
  mixins: [payPassword],
  mounted() {
    if (this.$store.getters.config.is_point_transfer) {
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({
        errorType: "fail",
        errorText:
          "未开启" + this.balanceText + this.pointText + this.pageTypeText,
        showFoot: false
      });
    }
  },
  methods: {
    change(e) {
      this.params.money = null;
    },
    onInput(e) {
      let money = this.paramText[this.params.types].money;
      let value = parseFloat(e);
      value && value > money && (this.params.money = money);
    },
    onPayPassword(e) {
      this.password = e;
      this.onExchange();
    },
    onExchange() {
      if (!this.params.money || this.params.money < 0) {
        return this.$Toast(this.paramText[this.params.types].inputPlaceholder);
      }
      this.isLoading = true;
      this.validPayPassword(this.password)
        .then(() => {
          EXCHANGE_BALANCEPOINT(this.params)
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
