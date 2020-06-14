<template>
  <div class="content">
    <div class="head">
      <div class="info">
        <div class="img">
          <img :src="shopkeeperInfo.user_headimg" />
        </div>
        <div class="text">
          <div>{{shopkeeperInfo.member_name}}</div>
          <div>等级：{{shopkeeperInfo.shopkeeper_level_name}}</div>
          <div>加入时间：{{shopkeeperInfo.become_shopkeeper_time}}</div>
        </div>
      </div>
    </div>
    <van-cell
      title="等级中心"
      is-link
      :value="shopkeeperInfo.shopkeeper_level_time == '无期限' ? shopkeeperInfo.shopkeeper_level_time : shopkeeperInfo.shopkeeper_level_time+ '到期'"
      @click="toGrade"
    />

    <CellPanelGroup title="微店收益" :items="cellPanelItems">
      <van-row type="flex" justify="end" class="btn-group" slot="headRight">
        <van-button
          size="mini"
          type="danger"
          class="btn"
          :disabled="withdrawBtn"
          @click="$router.push('/microshop/profit/withdraw')"
        >提现</van-button>
        <van-button
          size="mini"
          type="danger"
          class="btn"
          @click="$router.push('/microshop/profit/detail')"
        >详情</van-button>
      </van-row>
    </CellPanelGroup>

    <CellCardGroup
      title="常用"
      :items="usuallyCardItems"
      cols="4"
      @click="({link})=>$router.push(link)"
    />
  </div>
</template>
<script>
import CellCardGroup from "@/components/CellCardGroup";
import CellPanelGroup from "@/components/CellPanelGroup";
export default {
  data() {
    return {};
  },
  props: {
    shopkeeperInfo: Object, // 店主信息
    incomelist: Object //收益
  },
  computed: {
    usuallyCardItems() {
      let items = [
        {
          text: "微店管理",
          icon: "v-icon-manage",
          link: "/microshop/set"
        },
        {
          text: "挑选商品",
          icon: "v-icon-take",
          link: "/microshop/choosegoods/category"
        },
        {
          text: "预览微店",
          icon: "v-icon-stock",
          link: "/microshop/previewshop"
        },
        {
          text: "分享微店",
          icon: "v-icon-Forward",
          link: "/microshop/qrcode"
        }
      ];
      if (this.$store.state.config.addons.credential) {
        items.push({
          text: "授权证书",
          icon: "v-icon-guarantee",
          link: "/microshop/certificate"
        });
      }
      return items;
    },
    cellPanelItems() {
      const incomelist = this.incomelist;
      return [
        { title: "可提现", text: incomelist.profit ? incomelist.profit : 0 },
        {
          title: "成功提现",
          text: incomelist.withdrawals ? incomelist.withdrawals : 0
        },
        {
          title: "累计收益",
          text: incomelist.total_profit ? incomelist.total_profit : 0
        }
      ];
    },
    withdrawBtn() {
      return this.incomelist.profit ? false : true;
    }
  },
  methods: {
    toGrade() {
      this.$router.push({ name: "microshop-gradecentre" });
    }
  },
  components: {
    CellPanelGroup,
    CellCardGroup
  }
};
</script>
<style scoped>
.content {
  padding-bottom: 70px;
}
.head {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 130px;
  display: flex;
  background: #d03840;
}

.head .info {
  display: flex;
  align-items: center;
  z-index: 1;
  margin-left: 30px;
}

.head .info .img {
  width: 60px;
  height: 60px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.head .info .img img {
  width: 100%;
  height: 100%;
}

.head .info .text {
  margin-left: 10px;
  color: #fff3d1;
  line-height: 1.6;
}
</style>

