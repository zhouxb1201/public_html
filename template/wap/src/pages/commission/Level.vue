<template>
  <Layout ref="load" class="commission-level bg-f8">
    <Navbar />
    <LevelCard
      :name="user_info.user_name"
      :img="user_info.user_headimg"
      :text="user_info.level_name"
    />
    <LevelTable :items="table.data" :title_list="table.title_list" :liWidth="150" />
    <LevelUpgrade :info="upgrade" />
    <LevelDemote :info="demote" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import LevelCard from "@/components/LevelCard";
import LevelTable from "@/components/LevelTable";
import LevelUpgrade from "./component/LevelUpgrade";
import LevelDemote from "./component/LevelDemote";
export default sfc({
  name: "commission-level",
  data() {
    return {
      user_info: {},
      table: {
        title_list: [
          "等级",
          "一级返佣",
          "二级返佣",
          "三级返佣",
          "一级推荐",
          "二级推荐",
          "三级推荐"
        ],
        data: []
      },
      upgrade: {},
      demote: {}
    };
  },
  computed: {},
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getUpbonusLevelInfo", {
          types: 4
        })
        .then(data => {
          $this.user_info = data.user;
          $this.upgrade = data.levelCondition;
          $this.demote = data.downlevelCondition;
          data.levels.forEach(e => {
            let list = {};
            list.row1 = e.level_name;
            if (e.recommend_type == 1) {
              list.row2 = $this.getRowText(e.commission1, e.commission_point1);
              list.row3 = $this.getRowText(e.commission2, e.commission_point2);
              list.row4 = $this.getRowText(e.commission3, e.commission_point3);
            } else if (e.recommend_type == 2) {
              list.row2 = $this.getCellText(
                e.commission11,
                e.commission_point11
              );
              list.row3 = $this.getCellText(
                e.commission22,
                e.commission_point22
              );
              list.row4 = $this.getCellText(
                e.commission33,
                e.commission_point33
              );
            }
            list.row5 = $this.getCellText(e.recommend1, e.recommend_point1);
            list.row6 = $this.getCellText(e.recommend2, e.recommend_point2);
            list.row7 = $this.getCellText(e.recommend3, e.recommend_point3);
            $this.table.data.push(list);
          });
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.success();
        });
    },
    getRowText(commission, point) {
      let text = null;
      let com = parseFloat(commission);
      let pot = parseFloat(point);
      if (com > 0 && pot > 0) {
        text = com + "%佣金 + " + pot + "%积分";
      } else if (com > 0 && pot == 0) {
        text = com + "%佣金";
      } else if (com == 0 && pot > 0) {
        text = pot + "%积分";
      } else {
        text = "--";
      }
      return text;
    },
    getCellText(commission, point) {
      let text = null;
      let com = parseFloat(commission);
      let pot = parseFloat(point);
      if (com > 0 && pot > 0) {
        text = com + "元佣金 + " + pot + "积分";
      } else if (com > 0 && pot == 0) {
        text = com + "元佣金";
      } else if (com == 0 && pot > 0) {
        text = pot + "积分";
      } else {
        text = "--";
      }
      return text;
    }
  },
  components: {
    LevelCard,
    LevelTable,
    LevelUpgrade,
    LevelDemote
  }
});
</script>

<style scoped>
</style>
