<template>
  <Layout ref="load" class="member-level bg-f8">
    <Navbar />
    <LevelCard
      :name="info.user_name ? info.user_name : info.username"
      :img="info.member_img"
      :growth_num="'成长值：'+info.growth_num"
      :text="'购买商品享受'+member_discount+'折优惠'"
      class="level-card"
    >
      <div slot="bottom">
        <LevelStep :items="info.member_level_list" />
      </div>
    </LevelCard>
    <LevelTable :items="table.data" :title_list="table.title_list" />
    <LevelTips :info="info" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import LevelCard from "@/components/LevelCard";
import LevelTable from "@/components/LevelTable";
import LevelStep from "./component/LevelStep";
import LevelTips from "./component/LevelTips";
import { GET_MEMBERLEVEL } from "@/api/member";
export default sfc({
  name: "member-level",
  data() {
    return {
      info: {},
      member_discount: null,
      table: {
        title_list: ["等级", "成长值", "折扣"],
        data: []
      }
    };
  },
  computed: {},
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_MEMBERLEVEL()
        .then(({ data }) => {
          $this.info = data;
          data.member_level_list.forEach(e => {
            let list = {};
            list.row1 = e.level_name;
            list.row2 = e.growth_num;
            list.row3 = parseFloat(e.goods_discount) + "折";
            $this.table.data.push(list);
          });
          $this.member_discount = parseFloat(data.member_discount);
          $this.$refs.load.success();
        })
        .catch(() => {});
    }
  },
  components: {
    LevelCard,
    LevelStep,
    LevelTable,
    LevelTips
  }
});
</script>

<style scoped>
.level-card >>> .head {
  height: 130px;
}
</style>
