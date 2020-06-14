<template>
  <Layout ref="load" class="channel-centre bg-f8">
    <Navbar />
    <Header :info="info" />
    <CellCardGroup title="订单" :items="orderCardItems" @click="({link})=>$router.push(link)" />
    <CellCardGroup
      title="常用"
      :items="usuallyCardItems"
      cols="3"
      @click="({link})=>$router.push(link)"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellCardGroup from "@/components/CellCardGroup";
import Header from "./component/Header";
import { GET_CENTREINFO } from "@/api/channel";
export default sfc({
  name: "channel-centre",
  data() {
    return {
      info: {}
    };
  },
  computed: {
    orderCardItems() {
      return [
        {
          text: "采购订单",
          icon: "v-icon-form",
          link: "/channel/order/list/purchase"
        },
        {
          text: "提货订单",
          icon: "v-icon-form",
          link: "/channel/order/list/pickupgoods"
        },
        {
          text: "出货订单",
          icon: "v-icon-form",
          link: "/channel/order/list/output"
        },
        {
          text: "零售订单",
          icon: "v-icon-form",
          link: "/channel/order/list/retail"
        }
      ];
    },
    usuallyCardItems() {
      let items = [
        {
          text: "云仓采购",
          icon: "v-icon-stock",
          link: "/channel/goods/purchase"
        },
        {
          text: "云仓提货",
          icon: "v-icon-take",
          link: "/channel/goods/pickupgoods"
        },
        {
          text: "云仓管理",
          icon: "v-icon-manage",
          link: "/channel/depot"
        },
        {
          text: "财务管理",
          icon: "v-icon-assets",
          link: "/channel/finance"
        },
        {
          text: "我的业绩",
          icon: "v-icon-channel",
          link: "/channel/achieve"
        },
        {
          text: "我的团队",
          icon: "v-icon-team",
          link: "/channel/team"
        }
      ];
      if (this.$store.state.config.addons.credential) {
        items.push({
          text: "授权证书",
          icon: "v-icon-guarantee",
          link: "/channel/certificate"
        });
      }
      return items;
    }
  },
  activated() {
    if (this.$store.state.config.addons.channel == 1) {
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
            this.$Toast(
              "请先成为" +
                this.$store.state.member.commissionSetText.distributor_name +
                "！"
            );
            this.$router.replace("/member/centre");
          }
        });
    } else {
      this.$refs.load.fail({ errorText: "未开启微商应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      GET_CENTREINFO()
        .then(({ data }) => {
          if (!data.is_channel) {
            $this.$refs.load.result();
            $this.$router.replace("/channel/apply");
          } else {
            $this.info = data;
            $this.$refs.load.success();
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    CellCardGroup,
    Header
  }
});
</script>
<style scoped>
</style>
