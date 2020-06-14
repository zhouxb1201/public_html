<template>
  <Layout ref="load" class="commission-centre bg-f8" :style="pageStyle">
    <Navbar :title="navbarTitle" />
    <InviteWechat />
    <Header :item="headItem" :info="info" />
    <CellPanelGroup
      :title="$store.state.member.commissionSetText.distribution_commission"
      :items="cellPanelItems"
    >
      <van-row type="flex" justify="end" class="btn-group" slot="headRight">
        <van-button size="mini" type="danger" class="btn" @click="onWithdraw">提现</van-button>
        <van-button
          size="mini"
          type="danger"
          class="btn"
          @click="$router.push('/commission/detail')"
        >详情</van-button>
      </van-row>
    </CellPanelGroup>
    <CustomGroup type="5" :items="items" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
import CellPanelGroup from "@/components/CellPanelGroup";
import Header from "./component/Header";
export default sfc({
  name: "commission-centre",
  data() {
    return {
      items: {},
      page: {},
      info: {},
      headItem: {}
    };
  },
  computed: {
    navbarTitle() {
      let title = this.page.title;
      if (title) {
        document.title = title;
      }
      return title;
    },
    cellPanelItems() {
      const {
        withdrawable_commission,
        withdrawals_commission,
        total_commission
      } = this.$store.state.member.commissionSetText;
      const info = this.info;
      return [
        {
          title: withdrawable_commission,
          text: info.commission ? info.commission : 0
        },
        {
          title: withdrawals_commission,
          text: info.withdrawals ? info.withdrawals : 0
        },
        {
          title: total_commission,
          text: info.total_commission ? info.total_commission : 0
        }
      ];
    },
    pageStyle() {
      return {
        background: this.page.background
      };
    }
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    if (this.$store.state.config.addons.distribution == 1) {
      this.$store
        .dispatch("isBistributor")
        .then(() => {
          this.loadData();
        })
        .catch(({ error, callback }) => {
          if (error) {
            this.$refs.load.fail();
          }
          if (callback) {
            this.$refs.load.result();
            this.$router.replace("/commission/apply");
          }
        });
    } else {
      this.$refs.load.fail({ errorText: "未开启分销应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getCommissionInfo", true)
        .then(data => {
          $this.info = data;
          $this.info.name = this.getName(data);
          $this.info.commission = parseFloat($this.info.commission);
          $this.info.withdrawals = parseFloat($this.info.withdrawals);
          $this.info.total_commission = parseFloat($this.info.total_commission);
          $this.info.reg_time = $this.info.apply_distributor_time;
          $this.getCustom();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    getName({ member_name, real_name }) {
      if (member_name) return member_name;
      if (real_name) return real_name;
      return "未设置昵称";
    },
    getCustom() {
      this.$store
        .dispatch("getCustom", {
          type: 5
        })
        .then(data => {
          if (data.template_data) {
            this.items = data.template_data.items;
            this.page = data.template_data.page;
            for (let i in this.items) {
              if (this.items[i].id == "commission_fixed") {
                this.headItem = this.items[i];
              }
              if(this.items[i].id == 'menu'){
                for (let j in this.items[i].data){
                  let oData = this.items[i].data[j]
                  const o = {
                    '/commission/order':{
                      num:this.info.agentordercount,
                      unit:'个'
                    },
                    '/commission/team':{
                      num:this.info.agentcount,
                      unit:'人'
                    },
                    '/commission/customer':{
                      num:this.info.customcount,
                      unit:'人'
                    },
                  }
                  const link = oData.linkurl
                  if(o[link] != undefined){
                    oData.num = o[link].num;
                    oData.unit = o[link].unit;
                  }
                }
              }
            }
          }
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    onWithdraw() {
      if (this.info.is_datum == 2) {
        this.$Toast("请先完善资料！");
        this.$router.push({ name: "commission-apply", hash: "#replenish" });
        return false;
      }
      const commission = this.info.commission ? this.info.commission : 0;
      if (!commission) {
        return this.$Toast(
          "提现" +
            this.$store.state.member.commissionSetText.commission +
            "为0，不可提现"
        );
      }
      this.$router.push("/commission/withdraw");
    }
  },
  components: {
    InviteWechat,
    CustomGroup,
    CellPanelGroup,
    Header
  }
});
</script>

<style scoped>
.btn-group {
  overflow: hidden;
}

.btn-group .btn {
  margin-left: 6px;
}
</style>
