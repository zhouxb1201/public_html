<template>
  <div class="box">
    <van-cell-group :border="false" class="van-hairline--bottom">
      <van-field
        label="CPU"
        type="number"
        :placeholder="'最多可'+typeText+maxNum[type].cpu+'EOS'"
        @keydown="keydown"
        v-model="params.cpu"
      />
      <van-field
        label="NET"
        type="number"
        :placeholder="'最多可'+typeText+maxNum[type].net+'EOS'"
        @keydown="keydown"
        v-model="params.net"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :loading="isLoading"
        @click="onSubmit"
      >{{typeText}}</van-button>
    </div>
    <div class="note">
      <div class="title">注意：</div>
      <div class="text" v-for="(item,index) in note[type]" :key="index">{{item}}</div>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      @confirm="onPayPassword"
      @cancel="isLoading=false"
      :load-data="initBlockchainSetData"
    />
  </div>
</template>

<script>
import { SUB_BLOCKCHAINRESOURCE } from "@/api/blockchain";
import DialogPayPassword from "@/components/DialogPayPassword";
import { handleInput } from "@/utils/util";
import { payPassword } from "@/mixins";
import { yuan, bi } from "@/utils/filter";
export default {
  data() {
    return {
      params: {
        password: ""
      },
      note: {
        mortgage: [
          "- 使用EOS钱包产生的任何交易都会占用CPU和NET。",
          "- 所占用的资源一般都在24小时后释放返还。",
          "- 你可以在任何时候赎回自己的抵押的资源。"
        ],
        redeem: [
          "- 仅能赎回自己抵押的资源。",
          "- 仅能赎回当前未占用的资源。",
          "- 当前未占用的资源需要等到24小时后才能赎回。"
        ]
      },

      isLoading: false
    };
  },
  model: {
    prop: "type"
  },
  props: {
    type: String,
    info: Object
  },
  mixins: [payPassword],
  computed: {
    typeText() {
      this.params = {};
      if (this.type == "mortgage") return "抵押";
      if (this.type == "redeem") return "赎回";
    },
    maxNum() {
      let obj = {
        mortgage: {
          cpu: 0,
          net: 0
        },
        redeem: {
          cpu: 0,
          net: 0
        }
      };
      if (this.type == "mortgage") {
        obj.mortgage.cpu = bi(this.info.balance);
        obj.mortgage.net = bi(this.info.balance);
      }
      if (this.type == "redeem") {
        obj.redeem.cpu = bi(this.info.cpuPrices);
        obj.redeem.net = bi(this.info.netPrices);
      }
      return obj;
    }
  },
  methods: {
    // 支付密码设置完成重新获取相关设置
    initBlockchainSetData() {
      this.$store.dispatch("getBlockchainSet", true);
    },
    keydown(e) {
      handleInput(e);
    },
    // 验证密码
    onPayPassword(password) {
      this.params.password = password;
      this.onSubmit();
    },
    validator(type) {
      const params = this.params;
      const maxCpuNum = parseFloat(this.maxNum[type].cpu);
      const maxNetNum = parseFloat(this.maxNum[type].net);
      if (!parseFloat(params.cpu) && !parseFloat(params.net)) {
        this.$Toast("请输入CPU或NET" + this.typeText + "的EOS！");
        return false;
      }
      if (params.cpu && parseFloat(params.cpu) > maxCpuNum) {
        this.$Toast("CPU最多可" + this.typeText + maxCpuNum + "EOS！");
        return false;
      }
      if (params.net && parseFloat(params.net) > maxNetNum) {
        this.$Toast("NET最多可" + this.typeText + maxNetNum + "EOS！");
        return false;
      }
      params.cpu = params.cpu ? bi(params.cpu) : "";
      params.net = params.net ? bi(params.net) : "";
      return params;
    },
    onSubmit() {
      const params = this.validator(this.type);
      if (params) {
        this.isLoading = true;
        this.validPayPassword(params.password, true)
          .then(() => {
            SUB_BLOCKCHAINRESOURCE(
              { type: this.type, typeText: this.typeText },
              params
            )
              .then(({ message }) => {
                this.$Toast.success(message);
                this.$router.replace("/blockchain/centre/eos");
                this.isLoading = false;
              })
              .catch(() => {
                this.isLoading = false;
              });
          })
          .catch(() => {
            this.isLoading = false;
            this.params.password = "";
          });
      }
    }
  },
  components: {
    DialogPayPassword
  }
};
</script>

<style scoped>
.box {
  background: #fff;
  padding-top: 10px;
}

.note {
  background: #fff;
  padding: 10px 15px;
  color: #909399;
  font-size: 12px;
  line-height: 1.6;
}
</style>
