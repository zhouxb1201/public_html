<template>
  <Layout ref="load" class="centre bg-f8">
    <Header class="header" />
    <CellPanelGroup class="survey-box" title="经营概况" cols="3" show-all :items="surveyList" />
    <CellCardGroup
      v-if="cardList.length"
      :items="cardList"
      :show-head="false"
      cols="4"
      @click="({link})=>$router.push(link)"
    />
  </Layout>
</template>

<script>
import Header from "./component/Header";
import CellPanelGroup from "@/components/CellPanelGroup";
import CellCardGroup from "@/components/CellCardGroup";
export default {
  name: "centre",
  data() {
    return {};
  },
  computed: {
    surveyList() {
      let items = [];
      const {
        sale_money,
        sale_count,
        finished_count,
        unfinished_count,
        unfinished_after_order
      } = this.$store.state.config.storeSurvey;
      items = [
        {
          title: "今日营业额",
          text: sale_money || 0
        },
        {
          title: "今日订单数",
          text: sale_count || 0
        },
        { title: "待处理售后", text: unfinished_after_order || 0 },
        {
          title: "今日核销订单",
          text: finished_count || 0
        },
        {
          title: "待核销订单",
          text: unfinished_count || 0
        }
      ];
      return items;
    },
    cardList() {
      const items = [];
      const jobsOperate = this.$store.state.account.jobsOperate;
      jobsOperate.forEach(e => {
        let obj = {};
        obj.text = e.module_name;
        switch (e.module_id) {
          case "1":
            obj.icon = "qr";
            obj.link = "/verify";
            break;
          case "4":
            obj.icon = "form";
            obj.link = "/order/list";
            break;
          case "5":
            obj.icon = "stock";
            obj.link = "/statistic";
            break;
          case "6":
            obj.icon = "manage";
            obj.link = "/manage/list";
            break;
          case "7":
            obj.icon = "take";
            obj.link = "/goods";
            break;
          case "8":
            obj.icon = "form";
            obj.link = "/order/after";
            break;
          case "9":
            obj.icon = "text1";
            obj.link = "/verify/log";
            break;
        }
        items.push(obj);
      });
      return items;
    }
  },
  created() {
    const $this = this;
    $this.$store
      .dispatch("getAccountInfo")
      .then(info => {
        $this.$store.dispatch("getStoreSurvey");
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  components: {
    Header,
    CellPanelGroup,
    CellCardGroup
  }
};
</script>

<style scoped>
.header >>> .info {
  padding-bottom: 30px;
}

.survey-box {
  margin-top: -30px;
}
</style>