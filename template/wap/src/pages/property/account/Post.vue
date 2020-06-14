<template>
  <div class="property-account-post bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell title="账号类型" class="cell-panel" v-if="pageType == 'add'">
      <van-radio-group v-model="type" class="cell-radio-group">
        <van-radio
          :name="item.type"
          v-for="(item, index) in columns"
          :key="index"
          >{{ item.text }}</van-radio
        >
      </van-radio-group>
    </van-cell>
    <component
      :is="cellGroupName"
      :loading="isLoading"
      :params="params"
      :page-type="pageType"
      @save="onSave"
    />
    <PopupBankCardSms
      v-model="showBankCardSms"
      type="signing"
      :params="params"
      @success="signingSuccess"
      @close="signingClose"
    />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import CellAccountGroup from "./component/CellAccountGroup";
import CellBankGroup from "./component/CellBankGroup";
import PopupBankCardSms from "@/components/PopupBankCardSms";
import { SET_ASSETACCOUNT } from "@/api/property";
import { _decode, _encode } from "@/utils/base64";
export default sfc({
  name: "property-account-post",
  data() {
    const info = this.$route.query.info
      ? JSON.parse(_decode(this.$route.query.info))
      : {};
    return {
      isLoading: false,
      type: info.type || null,
      params: info,
      showBankCardSms: false
    };
  },
  watch: {
    type(e) {
      if (this.pageType == "add") {
        this.params = {};
      }
    }
  },
  computed: {
    navbarTitle() {
      let title = this.$route.hash === "#edit" ? "编辑账户" : "新增账户";
      if (title) document.title = title;
      return title;
    },
    pageType() {
      return this.$route.hash === "#edit" ? "edit" : "add";
    },
    cellGroupName() {
      const type = this.type;
      let name = "";
      if (type == 3) {
        name = "CellAccountGroup";
      } else if (type == 1 || type == 4) {
        name = "CellBankGroup";
      }
      return name;
    },
    columns() {
      const withdrawType = this.$store.getters.config.withdraw_conf
        .withdraw_message;
      let arr = [];
      withdrawType.forEach(e => {
        if (e == 3) {
          arr.push({
            text: "支付宝",
            type: e
          });
        }
        if (e == 1 || e == 4) {
          arr.push({
            text: "银行卡",
            type: e
          });
        }
      });
      return arr;
    }
  },
  created() {
    const withdrawType = this.$store.getters.config.withdraw_conf
      .withdraw_message;
    if (this.pageType == "add") {
      withdrawType.some(e => {
        if (e == 3) {
          this.type = e;
          return true;
        }
        if (e == 1 || e == 4) {
          this.type = e;
          return true;
        }
      });
    } else {
      withdrawType.forEach(e => {
        if (this.type == 4 && e == 1) {
          // 如果编辑类型为手动打款，而平台提现设置开启自动打款，则type修改为自动打款类型
          this.type = 1;
        }
      });
    }
  },
  methods: {
    signingClose() {
      this.isLoading = false;
    },
    //签约成功
    signingSuccess() {
      this.$router.replace("/property/account");
    },
    onSave(params) {
      params.type = this.type;
      this.isLoading = true;
      // console.log(params);
      // return;
      SET_ASSETACCOUNT(this.pageType, params)
        .then(({ code, data, message }) => {
          if (code == 1) {
            if (data.thpinfo) {
              this.params.thpinfo = data.thpinfo;
              this.showBankCardSms = true;
            } else {
              this.$Toast("获取短信验证失败");
              this.isLoading = false;
            }
          } else {
            this.$Toast.success(message);
            this.$router.replace("/property/account");
          }
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  components: {
    CellAccountGroup,
    CellBankGroup,
    PopupBankCardSms
  }
});
</script>

<style scoped></style>
